<?php
require_once 'models/Competition.php';
require_once 'models/Participant.php';
require_once 'models/Score.php';
require_once 'models/Series.php';
require_once 'core/Auth.php';
require_once 'core/CSRF.php';

class AdminController {

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
                die("Token keamanan tidak valid.");
            }
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            if (Auth::login($email, $password)) {
                header('Location: ' . BASE_URL . '/admin/dashboard');
                exit;
            } else {
                $error = "Email atau password salah.";
            }
        }
        require_once 'views/admin/login.php';
    }

    public function dashboard() {
        Auth::requireLogin();
        $compModel = new Competition();
        $competitions = $compModel->getAll();
        require_once 'views/admin/dashboard.php';
    }

    public function competitions() {
        Auth::requireLogin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CSRF::verify($_POST['csrf_token'] ?? '')) die("Token tidak valid.");
            $compModel = new Competition();
            $compModel->create($_POST['name'], $_POST['type']);
            header('Location: ' . BASE_URL . '/admin/dashboard');
            exit;
        }
        require_once 'views/admin/competitions.php';
    }

    public function rounds($id) {
        Auth::requireLogin();
        $compModel = new Competition();
        $competition = $compModel->getById($id);
        $categories = $compModel->getCategories($id);
        $rounds = $compModel->getRounds($id);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CSRF::verify($_POST['csrf_token'] ?? '')) die("Token tidak valid.");
            if (isset($_POST['add_category'])) {
                $compModel->addCategory($id, $_POST['cat_name'], $_POST['max_score'], $_POST['min_score']);
            } elseif (isset($_POST['add_round'])) {
                $compModel->addRound(
                    $id,
                    $_POST['category_id'],
                    $_POST['round_name'],
                    $_POST['format'],
                    $_POST['shots_per_rambahan'],
                    $_POST['total_rambahan'],
                    $_POST['face_target_count'],
                    $_POST['cushion_count'],
                    $_POST['allowed_gender'] ?? 'semua',
                    $_POST['shooting_orders'] ?? 2,
                    isset($_POST['has_cushion_champion']) ? 1 : 0
                );
            }
            header("Location: " . BASE_URL . "/admin/rounds/$id");
            exit;
        }
        require_once 'views/admin/rounds.php';
    }

	public function generateLinks($round_id) {
		Auth::requireLogin();
		$scoreModel = new Score();
		$round = $scoreModel->getRoundById($round_id);
		if ($round['status'] === 'finished') die("Babak sudah selesai.");

		$allAssignments = $scoreModel->getAssignmentsByRound($round_id);
		$db = new Database();
		$conn = $db->getConnection();
		// Ambil label bantalan dan target
		foreach ($allAssignments as &$a) {
			$stmt = $conn->prepare("
				SELECT ft.label as target_label, fc.label as cushion_label
				FROM face_targets ft
				LEFT JOIN face_cushions fc ON ft.cushion_id = fc.id
				WHERE ft.id = ?
			");
			$stmt->execute([$a['face_target_id']]);
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			if ($row) {
				$a['target_label'] = $row['target_label'];
				$a['cushion_label'] = $row['cushion_label'] ?? '';
			} else {
				$a['target_label'] = 'Target ' . $a['target_number'];
				$a['cushion_label'] = '';
			}
		}
		unset($a);

		$usedTargetIds = $scoreModel->getTargetIdsWithInputSession($round_id);

		// Hanya assignment yang belum memiliki link input
		$availableAssignments = [];
		foreach ($allAssignments as $a) {
			if (!in_array($a['face_target_id'], $usedTargetIds)) {
				$availableAssignments[] = $a;
			}
		}

		// Kelompokkan per target
		$targetGroups = [];
		foreach ($availableAssignments as $a) {
			$targetGroups[$a['face_target_id']][] = $a;
		}

		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			if (!CSRF::verify($_POST['csrf_token'] ?? '')) die("Token tidak valid.");

			$targets = $_POST['targets'] ?? [];
			if (empty($targets)) {
				$error = "Pilih minimal satu target.";
			} else {
				// Hanya target yang memiliki peserta yang bisa dipilih
				$validTargets = array_intersect($targets, array_keys($targetGroups));
				if (empty($validTargets)) {
					$error = "Target yang dipilih tidak memiliki peserta.";
				} else {
					$password = $_POST['password'] ?? '';
					$usePassword = !isset($_POST['no_password']);
					$hashed = $usePassword ? password_hash($password, PASSWORD_BCRYPT) : '__NO_PASSWORD__';

					$slug = substr(md5(uniqid()), 0, 8);
					$scoreModel->createInputSession($round_id, $slug, $hashed, $validTargets);
					header("Location: " . BASE_URL . "/admin/generate-links/$round_id");
					exit;
				}
			}
		}

		$sessions = $scoreModel->getInputSessions($round_id);
		require_once 'views/admin/generate_links.php';
	}
	
	public function openRound($round_id) {
		Auth::requireLogin();
		$round = (new Score())->getRoundById($round_id);
		if (!$round) die("Babak tidak ditemukan.");
		if ($round['status'] !== 'finished') die("Babak belum selesai.");

		$conn = (new Database())->getConnection();
		$conn->prepare("UPDATE rounds SET status = 'pending' WHERE id = ?")->execute([$round_id]);

		header("Location: " . BASE_URL . "/admin/rounds/" . $round['competition_id']);
		exit;
	}

	public function unlockSession($session_id) {
		Auth::requireLogin();
		$conn = (new Database())->getConnection();
		$conn->prepare("UPDATE input_sessions SET status = 'active' WHERE id = ?")->execute([$session_id]);
		header("Location: " . $_SERVER['HTTP_REFERER']);
		exit;
	}

	public function correction($round_id) {
		Auth::requireLogin();
		$scoreModel = new Score();
		$round = $scoreModel->getRoundById($round_id);
		if (!$round) die("Babak tidak ditemukan.");

		$compModel = new Competition();
		$competition = $compModel->getById($round['competition_id']);
		$category = $compModel->getCategoryById($round['category_id']);

		$rambahans = $scoreModel->getRambahanByRound($round_id);
		$participants = $scoreModel->getParticipantsByRound($round_id);

		// Ambil data shots untuk semua peserta dan semua rambahan
		$shotsData = [];
		foreach ($participants as $p) {
			$participantShots = [];
			foreach ($rambahans as $rambahan) {
				$shots = $scoreModel->getShotsByRambahanParticipant($rambahan['id'], $p['id']);
				$participantShots[$rambahan['id']] = $shots;
			}
			$shotsData[$p['id']] = $participantShots;
		}

		$shotsPerRambahan = (int)$round['shots_per_rambahan'];
		$totalRambahan = (int)$round['total_rambahan'];

		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			// Simpan perubahan skor dari grid
			if (!CSRF::verify($_POST['csrf_token'] ?? '')) die("Token tidak valid.");
			$scores = $_POST['score'] ?? []; // struktur: [rambahan_id][participant_id][shot_number] = value
			$xs = $_POST['is_x'] ?? [];
			foreach ($scores as $rambahan_id => $participantScores) {
				foreach ($participantScores as $participant_id => $shots) {
					foreach ($shots as $shot_number => $value) {
						$isX = isset($xs[$rambahan_id][$participant_id][$shot_number]) ? 1 : 0;
						$scoreModel->updateShot($rambahan_id, $participant_id, $shot_number, $value, $isX, $_SESSION['user_id']);
					}
				}
			}
			header("Location: " . BASE_URL . "/admin/correction/$round_id");
			exit;
		}

		require_once 'views/admin/correction_grid.php';
	}

public function correctionPdf($round_id) {
    Auth::requireLogin();
    $scoreModel = new Score();
    $round = $scoreModel->getRoundById($round_id);
    if (!$round) die("Babak tidak ditemukan.");

    $compModel = new Competition();
    $competition = $compModel->getById($round['competition_id']);
    $category = $compModel->getCategoryById($round['category_id']);

    $rambahans = $scoreModel->getRambahanByRound($round_id);
    $participants = $scoreModel->getParticipantsByRound($round_id);
    $shotsData = [];
    foreach ($participants as $p) {
        $participantShots = [];
        foreach ($rambahans as $rambahan) {
            $shots = $scoreModel->getShotsByRambahanParticipant($rambahan['id'], $p['id']);
            $participantShots[$rambahan['id']] = $shots;
        }
        $shotsData[$p['id']] = $participantShots;
    }

    $shotsPerRambahan = (int)$round['shots_per_rambahan'];
    $totalRambahan = (int)$round['total_rambahan'];

    ob_start();
    require 'views/admin/correction_pdf.php';
    $html = ob_get_clean();

    if (file_exists('libs/dompdf/autoload.inc.php')) {
        require_once 'libs/dompdf/autoload.inc.php';
        $dompdf = new Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        // Gunakan ukuran kertas A2 landscape agar semua kolom muat
        $dompdf->setPaper('A2', 'landscape');
        $dompdf->render();
        $dompdf->stream("scoresheet-{$round['name']}.pdf", ["Attachment" => true]);
    } else {
        echo $html;
        echo '<script>window.print();</script>';
    }
}

    public function logout() {
        Auth::logout();
        header('Location: ' . BASE_URL . '/admin');
    }

    public function finishRound($round_id) {
        Auth::requireLogin();
        $scoreModel = new Score();
        $round = $scoreModel->getRoundById($round_id);
        $db = new Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("UPDATE rounds SET status = 'finished' WHERE id = ?");
        $stmt->execute([$round_id]);
        header("Location: " . BASE_URL . "/admin/rounds/" . $round['competition_id']);
    }

    public function seriesPoints($competition_id) {
        Auth::requireLogin();
        $compModel = new Competition();
        $competition = $compModel->getById($competition_id);
        if (!$competition || $competition['type'] !== 'series') die("Hanya untuk series.");
        $seriesModel = new Series();
        $existingRules = $seriesModel->getPointsRule($competition_id);
        $rules = [];
        foreach ($existingRules as $r) { $rules[$r['rank']] = $r['points']; }
        require_once 'views/admin/series_points.php';
    }

    public function saveSeriesPoints($competition_id) {
        Auth::requireLogin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CSRF::verify($_POST['csrf_token'] ?? '')) die("Token tidak valid.");
            $points = $_POST['points'] ?? [];
            $db = new Database();
            $conn = $db->getConnection();
            $stmt = $conn->prepare("DELETE FROM series_points_rules WHERE competition_id = ?");
            $stmt->execute([$competition_id]);
            $insert = $conn->prepare("INSERT INTO series_points_rules (competition_id, `rank`, points) VALUES (?, ?, ?)");
            foreach ($points as $rank => $point) {
                if ($point !== '') $insert->execute([$competition_id, $rank, $point]);
            }
        }
        header("Location: " . BASE_URL . "/admin/series-points/$competition_id");
        exit;
    }

    public function editRound($round_id) {
        Auth::requireLogin();
        $scoreModel = new Score();
        $round = $scoreModel->getRoundById($round_id);
        if (!$round) die("Babak tidak ditemukan.");
        $compModel = new Competition();
        $competition = $compModel->getById($round['competition_id']);
        $categories = $compModel->getCategories($round['competition_id']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CSRF::verify($_POST['csrf_token'] ?? '')) die("Token tidak valid.");
            $conn = (new Database())->getConnection();
            $stmt = $conn->prepare("UPDATE rounds SET category_id=?, name=?, format=?, shots_per_rambahan=?, total_rambahan=?, face_target_count=?, cushion_count=?, allowed_gender=?, shooting_orders=?, has_cushion_champion=? WHERE id=?");
            $stmt->execute([
                $_POST['category_id'],
                $_POST['round_name'],
                $_POST['format'],
                $_POST['shots_per_rambahan'],
                $_POST['total_rambahan'],
                $_POST['face_target_count'],
                $_POST['cushion_count'],
                $_POST['allowed_gender'] ?? 'semua',
                $_POST['shooting_orders'] ?? 2,
                isset($_POST['has_cushion_champion']) ? 1 : 0,
                $round_id
            ]);
            header("Location: " . BASE_URL . "/admin/rounds/" . $round['competition_id']);
            exit;
        }
        require_once 'views/admin/edit_round.php';
    }

    public function deleteRound($round_id) {
        Auth::requireLogin();
        $round = (new Score())->getRoundById($round_id);
        if (!$round || $round['status'] === 'finished') die("Tidak dapat menghapus.");

        $conn = (new Database())->getConnection();
        $conn->prepare("DELETE FROM input_sessions WHERE round_id=?")->execute([$round_id]);
        $conn->prepare("DELETE FROM participant_face_assignments WHERE round_id=?")->execute([$round_id]);
        // Perbaikan query raw
        $stmt = $conn->prepare("SELECT id FROM rambahans WHERE round_id = ?");
        $stmt->execute([$round_id]);
        $rambahanIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($rambahanIds as $rambahan_id) {
            $conn->prepare("DELETE FROM shots WHERE rambahan_id=?")->execute([$rambahan_id]);
        }
        $conn->prepare("DELETE FROM rambahans WHERE round_id=?")->execute([$round_id]);
        $conn->prepare("DELETE FROM face_targets WHERE round_id=?")->execute([$round_id]);
        $conn->prepare("DELETE FROM face_cushions WHERE round_id=?")->execute([$round_id]);
        $conn->prepare("DELETE FROM rounds WHERE id=?")->execute([$round_id]);
        header("Location: " . BASE_URL . "/admin/rounds/" . $round['competition_id']);
    }

    public function addSessionTargets($round_id) {
        Auth::requireLogin();
        $round = (new Score())->getRoundById($round_id);
        if (!$round || $round['status'] === 'finished') die("Babak tidak valid.");
        $this->addSessionTargetsDirect($round_id);
        header("Location: " . BASE_URL . "/admin/assign-targets/$round_id");
        exit;
    }

    public function autoAddSessionIfNeeded($round_id) {
        Auth::requireLogin();
        $scoreModel = new Score();
        $round = $scoreModel->getRoundById($round_id);
        if (!$round || $round['status'] === 'finished') return false;

        $cushions = $scoreModel->getCushionsWithTargets($round_id);
        $totalSlots = 0;
        foreach ($cushions as $c) {
            $totalSlots += count($c['targets']) * (int)($round['shooting_orders'] ?? 2);
        }

        $allowedGender = $round['allowed_gender'] ?? 'semua';
        $participants = $scoreModel->getParticipantsForRound($round_id, $allowedGender);

        if (count($participants) > $totalSlots) {
            $this->addSessionTargetsDirect($round_id);
            return true;
        }
        return false;
    }

    private function addSessionTargetsDirect($round_id) {
        $round = (new Score())->getRoundById($round_id);
        $conn = (new Database())->getConnection();
        $cushionCount = (int)$round['cushion_count'];
        $targetPerCushion = intdiv((int)$round['face_target_count'], $cushionCount);

        $stmt = $conn->prepare("SELECT MAX(session) FROM face_cushions WHERE round_id = ?");
        $stmt->execute([$round_id]);
        $maxSession = (int)$stmt->fetchColumn() ?: 0;
        $newSession = $maxSession + 1;

        $stmt = $conn->prepare("SELECT MAX(number) FROM face_targets WHERE round_id = ?");
        $stmt->execute([$round_id]);
        $maxTargetNum = (int)$stmt->fetchColumn() ?: 0;

        $targetLetters = range('A', 'Z');
        for ($b = 1; $b <= $cushionCount; $b++) {
            $cushionLabel = "Bantalan " . $b . " (Sesi " . $newSession . ")";
            $conn->prepare("INSERT INTO face_cushions (round_id, session, number, label) VALUES (?, ?, ?, ?)")
                 ->execute([$round_id, $newSession, $b, $cushionLabel]);
            $cushionId = $conn->lastInsertId();

            for ($t = 0; $t < $targetPerCushion; $t++) {
                $maxTargetNum++;
                $targetLabel = "Target " . ($targetLetters[$t] ?? $maxTargetNum);
                $conn->prepare("INSERT INTO face_targets (round_id, cushion_id, number, label) VALUES (?, ?, ?, ?)")
                     ->execute([$round_id, $cushionId, $maxTargetNum, $targetLabel]);
            }
        }
    }

    public function editCategory($category_id) {
        Auth::requireLogin();
        $compModel = new Competition();
        $category = $compModel->getCategoryById($category_id);
        if (!$category) die("Kategori tidak ditemukan.");
        $competition = $compModel->getById($category['competition_id']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CSRF::verify($_POST['csrf_token'] ?? '')) die("Token tidak valid.");
            $conn = (new Database())->getConnection();
            $stmt = $conn->prepare("UPDATE categories SET name=?, max_score_per_shot=?, min_score_per_shot=? WHERE id=?");
            $stmt->execute([$_POST['cat_name'], $_POST['max_score'], $_POST['min_score'], $category_id]);
            header("Location: " . BASE_URL . "/admin/rounds/" . $category['competition_id']);
            exit;
        }
        require_once 'views/admin/edit_category.php';
    }

    public function deleteCategory($category_id) {
        Auth::requireLogin();
        $compModel = new Competition();
        $category = $compModel->getCategoryById($category_id);
        if (!$category) die("Kategori tidak ditemukan.");
        $competition_id = $category['competition_id'];

        $conn = (new Database())->getConnection();
        $conn->prepare("DELETE FROM categories WHERE id = ?")->execute([$category_id]);

        header("Location: " . BASE_URL . "/admin/rounds/$competition_id");
        exit;
    }

    public function deleteLink($session_id) {
        Auth::requireLogin();
        $conn = (new Database())->getConnection();
        $stmt = $conn->prepare("SELECT round_id FROM input_sessions WHERE id = ?");
        $stmt->execute([$session_id]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($session) {
            $conn->prepare("DELETE FROM input_session_targets WHERE input_session_id = ?")->execute([$session_id]);
            $conn->prepare("DELETE FROM input_sessions WHERE id = ?")->execute([$session_id]);
            $round_id = $session['round_id'];
        } else {
            $round_id = $_GET['round_id'] ?? 1;
        }
        header("Location: " . BASE_URL . "/admin/generate-links/$round_id");
        exit;
    }
	
	public function unlockLink($session_id) {
		Auth::requireLogin();
		$conn = (new Database())->getConnection();
		$stmt = $conn->prepare("SELECT round_id FROM input_sessions WHERE id = ?");
		$stmt->execute([$session_id]);
		$session = $stmt->fetch(PDO::FETCH_ASSOC);
		if ($session) {
			$conn->prepare("UPDATE input_sessions SET status = 'active' WHERE id = ?")->execute([$session_id]);
			$round_id = $session['round_id'];
		} else {
			$round_id = $_GET['round_id'] ?? 1;
		}
		header("Location: " . BASE_URL . "/admin/generate-links/$round_id");
		exit;
	}
}