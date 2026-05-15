<?php
require_once 'models/Competition.php';
require_once 'models/Participant.php';
require_once 'models/Score.php';
require_once 'core/Auth.php';
require_once 'core/CSRF.php';

class CategoryParticipantController {

    // ======================== PESERTA KATEGORI ========================
    public function index($category_id) {
        Auth::requireLogin();
        $compModel = new Competition();
        $category = $compModel->getCategoryById($category_id);
        if (!$category) die("Kategori tidak ditemukan.");
        $competition = $compModel->getById($category['competition_id']);
        $participants = $compModel->getParticipantsByCategory($category_id);
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        require_once 'views/admin/category_participants.php';
    }

    public function add($category_id) {
        Auth::requireLogin();
        $compModel = new Competition();
        $category = $compModel->getCategoryById($category_id);
        if (!$category) die("Kategori tidak ditemukan.");
        $competition = $compModel->getById($category['competition_id']);

        $db = new Database();
        $conn = $db->getConnection();
        $clubs = $conn->query("SELECT id, name FROM clubs")->fetchAll(PDO::FETCH_ASSOC);

        $round_id = $_GET['round_id'] ?? null;

        if ($round_id) {
            $stmt = $conn->prepare("
                SELECT p.id, p.name, c.name as club_name 
                FROM participants p
                JOIN competition_participants cp ON p.id = cp.participant_id 
                    AND cp.competition_id = :competition_id
                LEFT JOIN clubs c ON p.club_id = c.id
                WHERE p.id NOT IN (
                    SELECT participant_id 
                    FROM participant_face_assignments 
                    WHERE round_id = :round_id
                )
                ORDER BY p.name ASC
            ");
            $stmt->execute([
                ':competition_id' => $category['competition_id'],
                ':round_id' => $round_id
            ]);
        } else {
            $stmt = $conn->prepare("
                SELECT p.id, p.name, c.name as club_name 
                FROM participants p
                JOIN competition_participants cp ON p.id = cp.participant_id 
                    AND cp.competition_id = :competition_id
                LEFT JOIN clubs c ON p.club_id = c.id
                ORDER BY p.name ASC
            ");
            $stmt->execute([':competition_id' => $category['competition_id']]);
        }
        $allParticipants = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CSRF::verify($_POST['csrf_token'] ?? '')) die("Token tidak valid.");
            if (!empty($_POST['existing_participant'])) {
                $participant_id = $_POST['existing_participant'];
            } else {
                $name = strtoupper(trim($_POST['name'] ?? ''));
                $club_name = strtoupper(trim($_POST['club_name'] ?? ''));
                $birth_date = $_POST['birth_date'] ?? '1900-01-01';
                $address = $_POST['address'] ?? '';
                $gender = $_POST['gender'] ?? null;

                // Cari atau buat klub (nama klub disimpan UPPERCASE)
                $club_id = null;
                if (!empty($club_name)) {
                    $stmt = $conn->prepare("SELECT id FROM clubs WHERE UPPER(name) = ?");
                    $stmt->execute([$club_name]);
                    $club = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($club) $club_id = $club['id'];
                    else {
                        $conn->prepare("INSERT INTO clubs (name) VALUES (?)")->execute([$club_name]);
                        $club_id = $conn->lastInsertId();
                    }
                }

                // Cek duplikat dengan fuzzy matching dalam kompetisi (case‑insensitive)
                $existingId = $this->findSimilarParticipantInCompetition($name, $club_id, $category['competition_id']);
                if ($existingId) {
                    $participant_id = $existingId;
                } else {
                    $partModel = new Participant();
                    $participant_id = $partModel->add($name, $club_id, $birth_date, $address, $gender);
                }
            }

            // Tambahkan ke kategori jika belum ada
            $check = $conn->prepare("SELECT id FROM competition_participants WHERE competition_id = ? AND participant_id = ? AND category_id = ?");
            $check->execute([$category['competition_id'], $participant_id, $category_id]);
            if (!$check->fetch()) {
                $compModel->addParticipantToCategory($category['competition_id'], $participant_id, $category_id);
            }

            $redirect = BASE_URL . "/cp/{$category_id}";
            if ($round_id) $redirect .= "?round_id=$round_id";
            header("Location: $redirect");
            exit;
        }

        require_once 'views/admin/add_category_participant.php';
    }

    public function import($category_id) {
        Auth::requireLogin();
        $compModel = new Competition();
        $category = $compModel->getCategoryById($category_id);
        if (!$category) die("Kategori tidak ditemukan.");
        $competition = $compModel->getById($category['competition_id']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
            if (!CSRF::verify($_POST['csrf_token'] ?? '')) die("Token tidak valid.");
            // Validasi file
            $allowedTypes = ['text/csv', 'application/vnd.ms-excel', 'text/plain', 'application/csv'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['csv_file']['tmp_name']);
            finfo_close($finfo);
            if (!in_array($mime, $allowedTypes) && pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION) !== 'csv') {
                die("File tidak diizinkan.");
            }
            if ($_FILES['csv_file']['size'] > 5 * 1024 * 1024) {
                die("Ukuran file maksimal 5MB.");
            }

            $file = $_FILES['csv_file']['tmp_name'];
            $partModel = new Participant();
            $db = new Database();
            $conn = $db->getConnection();

            // Buka file dan deteksi delimiter / header
            $handle = fopen($file, 'r');
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") rewind($handle);
            $firstLine = fgets($handle);
            if ($firstLine === false) { fclose($handle); die("File kosong."); }
            $countComma = count(str_getcsv($firstLine, ','));
            $countSemicolon = count(str_getcsv($firstLine, ';'));
            $delimiter = ($countSemicolon > $countComma) ? ';' : ',';
            rewind($handle);
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") rewind($handle);

            $possibleHeader = fgetcsv($handle, 0, $delimiter);
            $isHeader = false;
            if (is_array($possibleHeader) && count($possibleHeader) > 0) {
                $firstCell = trim(strtolower($possibleHeader[0]));
                if (in_array($firstCell, ['nama', 'name', 'peserta', 'klub', 'club', 'tanggallahir', 'birth'])) {
                    $isHeader = true;
                }
            }

            $duplicates = [];
            $imported = 0;

            $processRow = function($data) use ($conn, $partModel, $compModel, $category, &$duplicates, &$imported) {
                $name = strtoupper(trim($data[0] ?? ''));
                if (empty($name)) return;
                $club_name = strtoupper(trim($data[1] ?? ''));
                $birth = trim($data[2] ?? '');
                $address = trim($data[3] ?? '');
                $gender = trim($data[4] ?? '');

                if (empty($birth)) $birth = '1900-01-01';
                elseif (preg_match('/^\d{4}$/', $birth)) $birth .= '-01-01';

                $club_id = null;
                if (!empty($club_name)) {
                    $stmt = $conn->prepare("SELECT id FROM clubs WHERE UPPER(name) = ?");
                    $stmt->execute([$club_name]);
                    $club = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($club) $club_id = $club['id'];
                    else {
                        $conn->prepare("INSERT INTO clubs (name) VALUES (?)")->execute([$club_name]);
                        $club_id = $conn->lastInsertId();
                    }
                }

                // Cek duplikat dengan fuzzy matching dalam kompetisi (case‑insensitive)
				$existingId = $this->findSimilarParticipantInCompetition($name, $club_id, $competition_id);
				if ($existingId) {
					$participant_id = $existingId;
				} else {
					// Fallback: cek langsung ke tabel participants (jangan sampai duplikat)
					$fallbackStmt = $conn->prepare("SELECT id FROM participants WHERE UPPER(name) = ? AND club_id " . ($club_id ? "= ?" : "IS NULL"));
					$fallbackParams = [$name];
					if ($club_id) $fallbackParams[] = $club_id;
					$fallbackStmt->execute($fallbackParams);
					$fallbackRow = $fallbackStmt->fetch(PDO::FETCH_ASSOC);
					if ($fallbackRow) {
						$participant_id = $fallbackRow['id'];
					} else {
						$participant_id = $partModel->add($name, $club_id, $birth, $address, $gender);
					}
				}

                $dupCheck = $conn->prepare("SELECT id FROM competition_participants WHERE competition_id = ? AND participant_id = ? AND category_id = ?");
                $dupCheck->execute([$category['competition_id'], $participant_id, $category_id]);
                if ($dupCheck->fetch()) {
                    $duplicates[] = $name;
                } else {
                    $conn->prepare("INSERT INTO competition_participants (competition_id, participant_id, category_id) VALUES (?, ?, ?)")
                         ->execute([$category['competition_id'], $participant_id, $category_id]);
                    $imported++;
                }
            };

            if (!$isHeader && is_array($possibleHeader)) {
                $processRow($possibleHeader);
            }

            while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
                $processRow($data);
            }
            fclose($handle);

            $_SESSION['flash'] = ['imported' => $imported, 'duplicates' => $duplicates];
            header("Location: " . BASE_URL . "/cp/{$category_id}");
            exit;
        }
        require_once 'views/admin/import_category_participants.php';
    }

    public function edit($category_id, $participant_id) {
        Auth::requireLogin();
        $compModel = new Competition();
        $category = $compModel->getCategoryById($category_id);
        if (!$category) die("Kategori tidak ditemukan.");
        $competition = $compModel->getById($category['competition_id']);

        $conn = (new Database())->getConnection();
        $stmt = $conn->prepare("SELECT p.*, c.name as club_name FROM participants p LEFT JOIN clubs c ON p.club_id = c.id WHERE p.id = ?");
        $stmt->execute([$participant_id]);
        $participant = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$participant) die("Peserta tidak ditemukan.");

        $clubs = $conn->query("SELECT id, name FROM clubs")->fetchAll(PDO::FETCH_ASSOC);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CSRF::verify($_POST['csrf_token'] ?? '')) die("Token tidak valid.");
            $name = strtoupper(trim($_POST['name'] ?? ''));
            $club_name = strtoupper(trim($_POST['club_name'] ?? ''));
            $birth_date = $_POST['birth_date'] ?? '1900-01-01';
            $address = $_POST['address'] ?? '';
            $gender = $_POST['gender'] ?? null;

            $club_id = null;
            if (!empty($club_name)) {
                $stmt = $conn->prepare("SELECT id FROM clubs WHERE UPPER(name) = ?");
                $stmt->execute([$club_name]);
                $club = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($club) $club_id = $club['id'];
                else {
                    $conn->prepare("INSERT INTO clubs (name) VALUES (?)")->execute([$club_name]);
                    $club_id = $conn->lastInsertId();
                }
            }

            $stmt = $conn->prepare("UPDATE participants SET name=?, club_id=?, birth_date=?, address=?, gender=? WHERE id=?");
            $stmt->execute([$name, $club_id, $birth_date, $address, $gender, $participant_id]);

            header("Location: " . BASE_URL . "/cp/{$category_id}");
            exit;
        }
        require_once 'views/admin/edit_category_participant.php';
    }

    public function delete($category_id, $participant_id) {
        Auth::requireLogin();
        (new Database())->getConnection()->prepare("DELETE FROM competition_participants WHERE category_id=? AND participant_id=?")->execute([$category_id, $participant_id]);
        header("Location: " . BASE_URL . "/cp/{$category_id}");
        exit;
    }

    public function deleteBatch($category_id) {
        Auth::requireLogin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['participant_ids'])) {
            if (!CSRF::verify($_POST['csrf_token'] ?? '')) die("Token tidak valid.");
            $conn = (new Database())->getConnection();
            $stmt = $conn->prepare("DELETE FROM competition_participants WHERE category_id=? AND participant_id=?");
            foreach ($_POST['participant_ids'] as $pid) $stmt->execute([$category_id, $pid]);
        }
        header("Location: " . BASE_URL . "/cp/{$category_id}");
        exit;
    }

    public function downloadTemplate($category_id) {
        Auth::requireLogin();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="template_peserta.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['NAMA', 'KLUB', 'TANGGAL LAHIR (YYYY-MM-DD)', 'ALAMAT', 'JENIS KELAMIN']);
        fputcsv($output, ['BUDI SANTOSO', 'PANAH JAYA', '2010-05-12', 'Jl. Melati No. 1', 'LAKI-LAKI']);
        fclose($output);
        exit;
    }

    // ======================== PESERTA BABAK ========================
    public function roundParticipants($round_id) {
        Auth::requireLogin();
        $scoreModel = new Score();
        $round = $scoreModel->getRoundById($round_id);
        if (!$round) die("Babak tidak ditemukan.");
        $compModel = new Competition();
        $competition = $compModel->getById($round['competition_id']);
        $category = $compModel->getCategoryById($round['category_id']);

        $db = new Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT p.*, c.name as club_name FROM round_participants rp JOIN participants p ON rp.participant_id = p.id LEFT JOIN clubs c ON p.club_id = c.id WHERE rp.round_id = ? ORDER BY p.name");
        $stmt->execute([$round_id]);
        $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        require_once 'views/admin/round_participants.php';
    }

    public function addRoundParticipant($round_id) {
        Auth::requireLogin();
        $scoreModel = new Score();
        $round = $scoreModel->getRoundById($round_id);
        if (!$round) die("Babak tidak ditemukan.");
        $compModel = new Competition();
        $competition = $compModel->getById($round['competition_id']);
        $category = $compModel->getCategoryById($round['category_id']);

        $db = new Database();
        $conn = $db->getConnection();
        $clubs = $conn->query("SELECT id, name FROM clubs")->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $conn->prepare("
            SELECT DISTINCT p.id, p.name, c.name as club_name 
            FROM participants p
            JOIN competition_participants cp ON p.id = cp.participant_id 
                AND cp.competition_id = :competition_id
                AND cp.category_id = :category_id
            LEFT JOIN clubs c ON p.club_id = c.id
            WHERE p.id NOT IN (
                SELECT participant_id FROM round_participants WHERE round_id = :round_id
            )
            ORDER BY p.name ASC
        ");
        $stmt->execute([
            ':competition_id' => $round['competition_id'],
            ':category_id'    => $round['category_id'],
            ':round_id'       => $round_id
        ]);
        $allParticipants = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CSRF::verify($_POST['csrf_token'] ?? '')) die("Token tidak valid.");
            if (!empty($_POST['existing_participant'])) {
                $participant_id = $_POST['existing_participant'];
            } else {
                $name = strtoupper(trim($_POST['name'] ?? ''));
                $club_name = strtoupper(trim($_POST['club_name'] ?? ''));
                $birth_date = $_POST['birth_date'] ?? '1900-01-01';
                $address = $_POST['address'] ?? '';
                $gender = $_POST['gender'] ?? null;

                $club_id = null;
                if (!empty($club_name)) {
                    $stmt = $conn->prepare("SELECT id FROM clubs WHERE UPPER(name) = ?");
                    $stmt->execute([$club_name]);
                    $club = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($club) $club_id = $club['id'];
                    else {
                        $conn->prepare("INSERT INTO clubs (name) VALUES (?)")->execute([$club_name]);
                        $club_id = $conn->lastInsertId();
                    }
                }

                // Cek duplikat dengan fuzzy matching dalam kompetisi (case‑insensitive)
                $existingId = $this->findSimilarParticipantInCompetition($name, $club_id, $round['competition_id']);
                if ($existingId) {
                    $participant_id = $existingId;
                } else {
                    $partModel = new Participant();
                    $participant_id = $partModel->add($name, $club_id, $birth_date, $address, $gender);
                }
                // Tambahkan ke kategori jika belum
                $checkCat = $conn->prepare("SELECT id FROM competition_participants WHERE competition_id = ? AND participant_id = ? AND category_id = ?");
                $checkCat->execute([$round['competition_id'], $participant_id, $round['category_id']]);
                if (!$checkCat->fetch()) {
                    $compModel->addParticipantToCategory($round['competition_id'], $participant_id, $round['category_id']);
                }
            }

            $conn->prepare("INSERT IGNORE INTO round_participants (round_id, participant_id) VALUES (?, ?)")
                 ->execute([$round_id, $participant_id]);
            header("Location: " . BASE_URL . "/admin/round-participants/$round_id");
            exit;
        }
        require_once 'views/admin/add_round_participant.php';
    }

    public function deleteRoundParticipant($round_id, $participant_id) {
        Auth::requireLogin();
        (new Database())->getConnection()->prepare("DELETE FROM round_participants WHERE round_id=? AND participant_id=?")->execute([$round_id, $participant_id]);
        header("Location: " . BASE_URL . "/admin/round-participants/$round_id");
        exit;
    }

    public function deleteBatchRoundParticipants($round_id) {
        Auth::requireLogin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['participant_ids'])) {
            if (!CSRF::verify($_POST['csrf_token'] ?? '')) die("Token tidak valid.");
            $conn = (new Database())->getConnection();
            $stmt = $conn->prepare("DELETE FROM round_participants WHERE round_id=? AND participant_id=?");
            foreach ($_POST['participant_ids'] as $pid) $stmt->execute([$round_id, $pid]);
        }
        header("Location: " . BASE_URL . "/admin/round-participants/$round_id");
        exit;
    }

    public function importRoundParticipants($round_id) {
        Auth::requireLogin();
        $scoreModel = new Score();
        $round = $scoreModel->getRoundById($round_id);
        if (!$round) die("Babak tidak ditemukan.");
        $competition_id = $round['competition_id'];
        $category_id = $round['category_id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
            if (!CSRF::verify($_POST['csrf_token'] ?? '')) die("Token tidak valid.");
            // Validasi file
            $allowedTypes = ['text/csv', 'application/vnd.ms-excel', 'text/plain', 'application/csv'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['csv_file']['tmp_name']);
            finfo_close($finfo);
            if (!in_array($mime, $allowedTypes) && pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION) !== 'csv') {
                die("File tidak diizinkan.");
            }
            if ($_FILES['csv_file']['size'] > 5 * 1024 * 1024) {
                die("Ukuran file maksimal 5MB.");
            }

            $file = $_FILES['csv_file']['tmp_name'];
            $partModel = new Participant();
            $db = new Database();
            $conn = $db->getConnection();
            $compModel = new Competition();

            // Deteksi header otomatis
            $handle = fopen($file, 'r');
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") rewind($handle);
            $firstLine = fgets($handle);
            if ($firstLine === false) { fclose($handle); die("File kosong."); }
            $countComma = count(str_getcsv($firstLine, ','));
            $countSemicolon = count(str_getcsv($firstLine, ';'));
            $delimiter = ($countSemicolon > $countComma) ? ';' : ',';
            rewind($handle);
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") rewind($handle);

            $possibleHeader = fgetcsv($handle, 0, $delimiter);
            $isHeader = false;
            if (is_array($possibleHeader) && count($possibleHeader) > 0) {
                $firstCell = trim(strtolower($possibleHeader[0]));
                if (in_array($firstCell, ['nama', 'name', 'peserta', 'klub', 'club', 'tanggallahir', 'birth'])) {
                    $isHeader = true;
                }
            }

            $duplicates = [];
            $imported = 0;

            $processRow = function($data) use ($conn, $partModel, $compModel, $competition_id, $category_id, $round_id, &$duplicates, &$imported) {
                $name = strtoupper(trim($data[0] ?? ''));
                if (empty($name)) return;
                $club_name = strtoupper(trim($data[1] ?? ''));
                $birth = trim($data[2] ?? '');
                $address = trim($data[3] ?? '');
                $gender = trim($data[4] ?? '');

                if (empty($birth)) $birth = '1900-01-01';
                elseif (preg_match('/^\d{4}$/', $birth)) $birth .= '-01-01';

                $club_id = null;
                if (!empty($club_name)) {
                    $stmt = $conn->prepare("SELECT id FROM clubs WHERE UPPER(name) = ?");
                    $stmt->execute([$club_name]);
                    $club = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($club) $club_id = $club['id'];
                    else {
                        $conn->prepare("INSERT INTO clubs (name) VALUES (?)")->execute([$club_name]);
                        $club_id = $conn->lastInsertId();
                    }
                }

                // Cek duplikat dengan fuzzy matching dalam kompetisi (case‑insensitive)
				$existingId = $this->findSimilarParticipantInCompetition($name, $club_id, $competition_id);
				if ($existingId) {
					$participant_id = $existingId;
				} else {
					// Fallback: cek langsung ke tabel participants (jangan sampai duplikat)
					$fallbackStmt = $conn->prepare("SELECT id FROM participants WHERE UPPER(name) = ? AND club_id " . ($club_id ? "= ?" : "IS NULL"));
					$fallbackParams = [$name];
					if ($club_id) $fallbackParams[] = $club_id;
					$fallbackStmt->execute($fallbackParams);
					$fallbackRow = $fallbackStmt->fetch(PDO::FETCH_ASSOC);
					if ($fallbackRow) {
						$participant_id = $fallbackRow['id'];
					} else {
						$participant_id = $partModel->add($name, $club_id, $birth, $address, $gender);
					}
				}

                // Tambahkan ke kategori jika belum
                $checkCat = $conn->prepare("SELECT id FROM competition_participants WHERE competition_id = ? AND participant_id = ? AND category_id = ?");
                $checkCat->execute([$competition_id, $participant_id, $category_id]);
                if (!$checkCat->fetch()) {
                    $compModel->addParticipantToCategory($competition_id, $participant_id, $category_id);
                }

                // Cek duplikat di round_participants
                $dupCheck = $conn->prepare("SELECT id FROM round_participants WHERE round_id = ? AND participant_id = ?");
                $dupCheck->execute([$round_id, $participant_id]);
                if ($dupCheck->fetch()) {
                    $duplicates[] = $name;
                } else {
                    $conn->prepare("INSERT INTO round_participants (round_id, participant_id) VALUES (?, ?)")
                         ->execute([$round_id, $participant_id]);
                    $imported++;
                }
            };

            if (!$isHeader && is_array($possibleHeader)) {
                $processRow($possibleHeader);
            }

            while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
                $processRow($data);
            }
            fclose($handle);

            $_SESSION['flash'] = ['imported' => $imported, 'duplicates' => $duplicates];
            header("Location: " . BASE_URL . "/admin/round-participants/$round_id");
            exit;
        }
        require_once 'views/admin/import_round_participants.php';
    }

    // ======================== FUZZY MATCHING (HANYA DALAM KOMPETISI, CASE‑INSENSITIVE) ========================
	private function findSimilarParticipantInCompetition($name, $club_id, $competition_id) {
		$words = explode(' ', trim($name));
		// Jika hanya 1 atau 2 kata, lakukan pencarian exact + LIKE
		if (count($words) <= 2) {
			$conn = (new Database())->getConnection();
			// Exact match dulu
			$sql = "SELECT p.id FROM participants p
					JOIN competition_participants cp ON p.id = cp.participant_id AND cp.competition_id = ?
					WHERE UPPER(p.name) = ?";
			$params = [$competition_id, $name];
			if ($club_id !== null) {
				$sql .= " AND p.club_id = ?";
				$params[] = $club_id;
			} else {
				$sql .= " AND p.club_id IS NULL";
			}
			$stmt = $conn->prepare($sql);
			$stmt->execute($params);
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			if ($row) return $row['id'];

			// Jika tidak ketemu, coba dengan LIKE (dua kata)
			if (count($words) == 2) {
				$pattern = '%' . $words[0] . '%' . $words[1] . '%';
				$sqlLike = "SELECT p.id FROM participants p
							JOIN competition_participants cp ON p.id = cp.participant_id AND cp.competition_id = ?
							WHERE UPPER(p.name) LIKE ?";
				$paramsLike = [$competition_id, strtoupper($pattern)];
				if ($club_id !== null) {
					$sqlLike .= " AND p.club_id = ?";
					$paramsLike[] = $club_id;
				} else {
					$sqlLike .= " AND p.club_id IS NULL";
				}
				$stmtLike = $conn->prepare($sqlLike);
				$stmtLike->execute($paramsLike);
				$rowLike = $stmtLike->fetch(PDO::FETCH_ASSOC);
				if ($rowLike) return $rowLike['id'];
			}
			return null;
		}

		// Untuk nama dengan 3 kata atau lebih, gunakan fuzzy matching seperti sebelumnya
		$conn = (new Database())->getConnection();

		$firstWord = strtoupper($words[0]);
		$stmt = $conn->prepare("
			SELECT p.id, p.name, p.club_id
			FROM participants p
			JOIN competition_participants cp ON p.id = cp.participant_id AND cp.competition_id = ?
			WHERE UPPER(p.name) LIKE ?
		");
		$stmt->execute([$competition_id, '%' . $firstWord . '%']);
		$candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

		if (empty($candidates)) return null;

		$scores = [];
		foreach ($candidates as $c) {
			$candidateWords = explode(' ', strtoupper($c['name']));
			$score = 0;
			foreach ($words as $w) {
				$upperW = strtoupper($w);
				if (strlen($upperW) < 2) continue; // abaikan inisial
				foreach ($candidateWords as $cw) {
					if (strpos($cw, $upperW) !== false) {
						$score++;
						break;
					}
				}
			}
			$scores[] = ['id' => $c['id'], 'score' => $score, 'club_id' => $c['club_id']];
		}

		usort($scores, function($a, $b) { return $b['score'] - $a['score']; });
		$bestScore = $scores[0]['score'];

		if ($bestScore < 2) {
			return null;
		}

		$bestCandidates = array_filter($scores, function($s) use ($bestScore) { return $s['score'] === $bestScore; });

		if (count($bestCandidates) === 1) {
			return array_shift($bestCandidates)['id'];
		}

		if ($club_id !== null) {
			foreach ($bestCandidates as $cand) {
				if ($cand['club_id'] == $club_id) {
					return $cand['id'];
				}
			}
		}

		return null;
	}
}