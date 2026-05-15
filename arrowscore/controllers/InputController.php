<?php
require_once 'models/Score.php';
require_once 'models/Competition.php';
require_once 'core/CSRF.php';

class InputController {

    public function login($slug) {
        $scoreModel = new Score();
        $session = $scoreModel->getSessionBySlug($slug);
        if (!$session) die("Link tidak valid.");
	// Cek apakah sesi ini tanpa password
		if ($session['hashed_password'] === '__NO_PASSWORD__') {
			$_SESSION['input_session_'.$slug] = [
				'session_id' => session_id(),
				'last_activity' => time()
			];
			header("Location: " . BASE_URL . "/input/$slug/grid");
		exit;
		}
        // Cek apakah sudah ada sesi aktif untuk slug ini
        $activeSession = $_SESSION['input_session_'.$slug] ?? null;
        $canTakeOver = false;
        if ($activeSession) {
            // Jika sesi sudah kadaluarsa (tidak ada aktivitas > 2 menit), boleh diambil alih
            if (time() - ($activeSession['last_activity'] ?? 0) > 300) {
                $canTakeOver = true;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
                die("Token keamanan tidak valid.");
            }
            $password = $_POST['password'] ?? '';
            if (password_verify($password, $session['hashed_password'])) {
                // Ambil alih jika diminta atau belum ada sesi
                if (!$activeSession || $canTakeOver || isset($_POST['takeover'])) {
                    $_SESSION['input_session_'.$slug] = [
                        'session_id' => session_id(),
                        'last_activity' => time()
                    ];
                    header("Location: " . BASE_URL . "/input/$slug/grid");
                    exit;
                } else {
                    $error = "Sesi sedang digunakan di perangkat lain. Klik 'Ambil Alih' untuk melanjutkan.";
                }
            } else {
                $error = "Password salah.";
            }
        }

        $csrfTokenField = CSRF::getTokenField();
        // Kirim flag apakah sesi sedang aktif
        $isActive = $activeSession && !$canTakeOver;
        $takeover = isset($_POST['takeover']);
        require_once 'views/input/login.php';
    }

	public function grid($slug) {
		if (!isset($_SESSION['input_session_'.$slug])) {
			header("Location: " . BASE_URL . "/input/$slug");
			exit;
		}
		$sessionData = $_SESSION['input_session_'.$slug];
		if (time() - ($sessionData['last_activity'] ?? 0) > 300) {
			unset($_SESSION['input_session_'.$slug]);
			header("Location: " . BASE_URL . "/input/$slug");
			exit;
		}
		$_SESSION['input_session_'.$slug]['last_activity'] = time();

		$scoreModel = new Score();
		$dbSession = $scoreModel->getSessionBySlug($slug);
		if (!$dbSession || !$dbSession['is_active']) die("Sesi tidak aktif.");
		$round = $scoreModel->getRoundById($dbSession['round_id']);
		if ($round['status'] === 'finished') die("Babak sudah selesai.");

		$isLocked = $scoreModel->isSessionLocked($dbSession['id']);

		$compModel = new Competition();
		$competition = $compModel->getById($round['competition_id']);
		$category = $compModel->getCategoryById($round['category_id']);
		$maxScore = (int)($category['max_score_per_shot'] ?? 10);
		$minScore = (int)($category['min_score_per_shot'] ?? 0);

		$targets = $scoreModel->getTargetsBySession($dbSession['id']);
		$participants = [];
		foreach ($targets as $t) {
			$assigned = $scoreModel->getParticipantsByTarget($t['id']);
			if (!empty($assigned)) {
				foreach ($assigned as $p) {
					$p['target_label'] = $t['label'];
					$p['cushion_label'] = $t['cushion_label'] ?? '';
					$participants[] = $p;
				}
			}
		}

		if (empty($participants)) {
			die("Tidak ada peserta ditemukan untuk sesi ini. Pastikan target sudah dialokasikan dan link input mencakup target yang tepat.");
		}

		$rambahans = $scoreModel->getRambahanByRound($dbSession['round_id']);
		$shots_per_rambahan = (int)($round['shots_per_rambahan'] ?? 0);
		$total_rambahan = (int)($round['total_rambahan'] ?? 0);

		$existingShots = [];
		foreach ($participants as $p) {
			foreach ($rambahans as $r) {
				$shots = $scoreModel->getShotsByRambahanParticipant($r['id'], $p['id']);
				foreach ($shots as $shot) {
					$existingShots[$p['id']][$r['id'].'_'.$shot['shot_number']] = [
						'value' => (int)$shot['score'],
						'isX' => (bool)$shot['is_x']
					];
				}
			}
		}

		require_once 'views/input/scoring_grid.php';
	}

    public function heartbeat($slug) {
        if (isset($_SESSION['input_session_'.$slug])) {
            $_SESSION['input_session_'.$slug]['last_activity'] = time();
            echo json_encode(['status' => 'ok']);
        } else {
            http_response_code(403);
            echo json_encode(['error' => 'session not found']);
        }
    }

	    public function save($slug) {
        if (!isset($_SESSION['input_session_'.$slug])) {
            http_response_code(403); die("Akses ditolak.");
        }
        $scoreModel = new Score();
        $session = $scoreModel->getSessionBySlug($slug);
        $round = $scoreModel->getRoundById($session['round_id']);
        $category = (new Competition())->getCategoryById($round['category_id']);
        $maxScore = (int)($category['max_score_per_shot'] ?? 10);
        $minScore = (int)($category['min_score_per_shot'] ?? 0);

        $data = json_decode(file_get_contents('php://input'), true);
        foreach ($data['scores'] as $participant_id => $rambahanData) {
            foreach ($rambahanData as $rambahan_id => $shots) {
                foreach ($shots as $shot_number => $shot) {
                    $score = $shot['value'] ?? null;
                    $isX = $shot['isX'] ?? false;
                    if ($score !== null && $score !== '') {
                        $score = (int)$score;
                        if ($score < $minScore || $score > $maxScore) {
                            http_response_code(422);
                            echo json_encode(['success' => false, 'error' => "Skor harus antara {$minScore} dan {$maxScore}."]);
                            return;
                        }
                        $scoreModel->saveShot($rambahan_id, $participant_id, $shot_number, $score, $isX);
                    }
                }
            }
        }
        echo json_encode(['success' => true]);
    }

    public function submit($slug) {
        if (!isset($_SESSION['input_session_'.$slug])) {
            http_response_code(403); die("Akses ditolak.");
        }
        $scoreModel = new Score();
        $session = $scoreModel->getSessionBySlug($slug);
        $round = $scoreModel->getRoundById($session['round_id']);
        $category = (new Competition())->getCategoryById($round['category_id']);
        $maxScore = (int)($category['max_score_per_shot'] ?? 10);
        $minScore = (int)($category['min_score_per_shot'] ?? 0);

        $data = json_decode(file_get_contents('php://input'), true);
        foreach ($data['scores'] as $participant_id => $rambahanData) {
            foreach ($rambahanData as $rambahan_id => $shots) {
                foreach ($shots as $shot_number => $shot) {
                    $score = $shot['value'] ?? 0;
                    $isX = $shot['isX'] ?? false;
                    $score = $score === '' ? 0 : (int)$score;
                    if ($score < $minScore || $score > $maxScore) {
                        http_response_code(422);
                        echo json_encode(['success' => false, 'error' => "Skor harus antara {$minScore} dan {$maxScore}."]);
                        return;
                    }
                    $scoreModel->saveShot($rambahan_id, $participant_id, $shot_number, $score, $isX);
                }
            }
        }

        // Kunci sesi
        $scoreModel->lockSession($session['id']);
        echo json_encode(['success' => true]);
    }
}