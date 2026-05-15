<?php
require_once 'models/Participant.php';
require_once 'models/Competition.php';
require_once 'models/Score.php';
require_once 'core/Auth.php';
require_once 'core/CSRF.php';
require_once 'controllers/AdminController.php';

class ParticipantController {

    public function index($competition_id) {
        Auth::requireLogin();
        $_SESSION['last_competition_id'] = $competition_id; // simpan untuk redirect edit
        $compModel = new Competition();
        $competition = $compModel->getById($competition_id);
        $categories = $compModel->getCategories($competition_id);

        $db = new Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("
            SELECT DISTINCT p.id, p.name, p.birth_date, p.address, p.gender, c.name as club_name
            FROM participants p
            JOIN competition_participants cp ON p.id = cp.participant_id
            LEFT JOIN clubs c ON p.club_id = c.id
            WHERE cp.competition_id = ?
            ORDER BY p.name ASC
        ");
        $stmt->execute([$competition_id]);
        $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require_once 'views/admin/participants.php';
    }

    public function add($competition_id) {
        Auth::requireLogin();
        $compModel = new Competition();
        $competition = $compModel->getById($competition_id);
        $categories = $compModel->getCategories($competition_id);
        $db = new Database();
        $conn = $db->getConnection();
        $clubs = $conn->query("SELECT id, name FROM clubs")->fetchAll(PDO::FETCH_ASSOC);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CSRF::verify($_POST['csrf_token'] ?? '')) die("Token tidak valid.");
            $name = strtoupper(trim($_POST['name']));
            $club_name = strtoupper(trim($_POST['club_name'] ?? ''));
            $birth_date = $_POST['birth_date'];
            $address = $_POST['address'] ?? '';
            $gender = $_POST['gender'] ?? null;
            $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;

            // Cari atau buat klub (UPPERCASE)
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

            $partModel = new Participant();
            $participant_id = $partModel->add($name, $club_id, $birth_date, $address, $gender);
            if ($category_id) {
                $compModel->addParticipantToCategory($competition_id, $participant_id, $category_id);
            }
            header("Location: " . BASE_URL . "/admin/participants/$competition_id");
            exit;
        }
        require_once 'views/admin/add_participant.php';
    }

    public function import($competition_id) {
        Auth::requireLogin();
        $compModel = new Competition();
        $competition = $compModel->getById($competition_id);
        $categories = $compModel->getCategories($competition_id);
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
            $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
            $partModel = new Participant();
            $db = new Database();
            $conn = $db->getConnection();

            $handle = fopen($file, 'r');
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") rewind($handle);
            $headerLine = fgets($handle);
            if ($headerLine === false) { fclose($handle); die("File kosong."); }
            $countComma = count(str_getcsv($headerLine, ','));
            $countSemicolon = count(str_getcsv($headerLine, ';'));
            $delimiter = ($countSemicolon > $countComma) ? ';' : ',';
            rewind($handle);
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") rewind($handle);
            fgetcsv($handle, 0, $delimiter);

            while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
                $name = strtoupper(trim($data[0] ?? ''));
                if (empty($name)) continue;
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

                $participant_id = $partModel->add($name, $club_id, $birth, $address, $gender);
                if ($category_id) $compModel->addParticipantToCategory($competition_id, $participant_id, $category_id);
            }
            fclose($handle);
            header("Location: " . BASE_URL . "/admin/participants/$competition_id");
            exit;
        }
        require_once 'views/admin/import_participants.php';
    }

    public function assignTargets($round_id) {
        Auth::requireLogin();
        $scoreModel = new Score();
        $round = $scoreModel->getRoundById($round_id);
        if (!$round || $round['status'] === 'finished') die("Babak tidak valid.");

        $adminCtrl = new AdminController();
        if ($adminCtrl->autoAddSessionIfNeeded($round_id)) {
            header("Location: " . BASE_URL . "/admin/assign-targets/$round_id");
            exit;
        }

        $compModel = new Competition();
        $competition = $compModel->getById($round['competition_id']);
        $category = $compModel->getCategoryById($round['category_id']);
        $allowedGender = $round['allowed_gender'] ?? 'semua';
        $participants = $scoreModel->getParticipantsForRound($round_id, $allowedGender);
        $cushions = $scoreModel->getCushionsWithTargets($round_id);
        $assignments = $scoreModel->getAssignmentsByRound($round_id);
        $shootingOrders = (int)($round['shooting_orders'] ?? 2);

        $assignMap = [];
        foreach ($assignments as $a) {
            $assignMap[$a['face_target_id']][$a['shooting_order']] = $a;
        }

        require_once 'views/admin/assign_targets.php';
    }

    public function saveAssignments($round_id) {
        Auth::requireLogin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CSRF::verify($_POST['csrf_token'] ?? '')) die("Token tidak valid.");
            $scoreModel = new Score();
            $scoreModel->clearAssignments($round_id);
            $assignments = $_POST['assignments'] ?? [];
            foreach ($assignments as $target_id => $slots) {
                foreach ($slots as $order => $participant_id) {
                    if (!empty($participant_id)) {
                        $scoreModel->addAssignment($round_id, $participant_id, $target_id, $order);
                    }
                }
            }
        }
        header("Location: " . BASE_URL . "/admin/assign-targets/$round_id");
        exit;
    }

    public function deleteFromCompetition($competition_id, $participant_id) {
        Auth::requireLogin();
        $conn = (new Database())->getConnection();
        $conn->prepare("DELETE FROM competition_participants WHERE competition_id = ? AND participant_id = ?")->execute([$competition_id, $participant_id]);
        header("Location: " . BASE_URL . "/admin/participants/$competition_id");
        exit;
    }

    public function deleteBatchFromCompetition($competition_id) {
        Auth::requireLogin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['participant_ids'])) {
            if (!CSRF::verify($_POST['csrf_token'] ?? '')) die("Token tidak valid.");
            $conn = (new Database())->getConnection();
            $stmt = $conn->prepare("DELETE FROM competition_participants WHERE competition_id = ? AND participant_id = ?");
            foreach ($_POST['participant_ids'] as $pid) $stmt->execute([$competition_id, $pid]);
        }
        header("Location: " . BASE_URL . "/admin/participants/$competition_id");
        exit;
    }

    public function editParticipant($participant_id) {
        Auth::requireLogin();
        $db = new Database();
        $conn = $db->getConnection();

        // Ambil data peserta
        $stmt = $conn->prepare("SELECT p.*, c.name as club_name FROM participants p LEFT JOIN clubs c ON p.club_id = c.id WHERE p.id = ?");
        $stmt->execute([$participant_id]);
        $participant = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$participant) die("Peserta tidak ditemukan.");

        $clubs = $conn->query("SELECT id, name FROM clubs")->fetchAll(PDO::FETCH_ASSOC);

        $competition_id = $_GET['competition_id'] ?? $_SESSION['last_competition_id'] ?? null;

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
                if ($club) {
                    $club_id = $club['id'];
                } else {
                    $conn->prepare("INSERT INTO clubs (name) VALUES (?)")->execute([$club_name]);
                    $club_id = $conn->lastInsertId();
                }
            }

            $stmt = $conn->prepare("UPDATE participants SET name=?, club_id=?, birth_date=?, address=?, gender=? WHERE id=?");
            $stmt->execute([$name, $club_id, $birth_date, $address, $gender, $participant_id]);

            if ($competition_id) {
                header("Location: " . BASE_URL . "/admin/participants/$competition_id");
            } else {
                header("Location: " . BASE_URL . "/admin/dashboard");
            }
            exit;
        }

        require_once 'views/admin/edit_participant.php';
    }
}