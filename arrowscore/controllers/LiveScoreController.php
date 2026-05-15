<?php
require_once 'models/Competition.php';
require_once 'models/Score.php';
require_once 'models/Series.php';

class LiveScoreController {
    public function index() {
        $compModel = new Competition();
        $competitions = $compModel->getAll();
        require_once 'views/livescore/index.php';
    }

    public function show($slug) {
        $compModel = new Competition();
        $comp = $compModel->getBySlug($slug);
        if (!$comp) die("Kompetisi tidak ditemukan.");
        $categories = $compModel->getCategories($comp['id']);
        $rounds = $compModel->getRounds($comp['id']);
        if (isset($_GET['round_id']) && is_numeric($_GET['round_id'])) {
            $round_id = (int)$_GET['round_id'];
            $scoreModel = new Score();
            $round = $scoreModel->getRoundById($round_id);
            if ($round && $round['competition_id'] == $comp['id']) {
                $scores = $scoreModel->getLiveScores($round_id);
				$category = $compModel->getCategoryById($round['category_id']);
                require_once 'views/livescore/public_board.php';
                return;
            }
        }
        require_once 'views/livescore/select_round.php';
    }

	public function viewAllocation($round_id) {
		$scoreModel = new Score();
		$round = $scoreModel->getRoundById($round_id);
		if (!$round) die("Babak tidak ditemukan.");

		$compModel = new Competition();
		$competition = $compModel->getById($round['competition_id']);
		$cushions = $scoreModel->getCushionsWithTargets($round_id);
		$assignments = $scoreModel->getAssignmentsByRound($round_id);
		$shootingOrders = (int)($round['shooting_orders'] ?? 2);
		$hasCushionChampion = (bool)($round['has_cushion_champion'] ?? false);

		// Susun assignments ke map
		$assignMap = [];
		foreach ($assignments as $a) {
			$assignMap[$a['face_target_id']][$a['shooting_order']] = $a;
		}

		// Hitung juara bantalan jika fitur aktif
		$cushionChampions = [];
		if ($hasCushionChampion) {
			foreach ($cushions as $cushion) {
				$champions = $scoreModel->getCushionChampions($round_id, $cushion['id']);
				$cushionChampions[$cushion['id']] = $champions;
			}
		}
$category = $compModel->getCategoryById($round['category_id']);
		require_once 'views/livescore/alokasi_target.php';
	}
	public function seriesStandings($slug) {
		$compModel = new Competition();
		$comp = $compModel->getBySlug($slug);
		if (!$comp || $comp['type'] !== 'series') die("Halaman hanya untuk Series.");

		$categories = $compModel->getCategories($comp['id']);
		if (empty($categories)) die("Belum ada kategori.");

		// Ambil category_id dari GET, jika kosong gunakan ID kategori pertama
		$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
		if ($category_id <= 0) {
			$category_id = (int)$categories[0]['id'];
		}

		$seriesModel = new Series();
		// 🔥 Pastikan filter kategori aktif
		$individualStandings = $seriesModel->getStandings($comp['id'], $category_id);
		// Klub tetap gabungan semua kategori (tanpa filter)
		$clubStandings = $seriesModel->getClubStandings($comp['id'], null);

		$selectedCategory = null;
		foreach ($categories as $cat) {
			if ($cat['id'] == $category_id) {
				$selectedCategory = $cat;
				break;
			}
		}

		require_once 'views/livescore/series_standings.php';
	}
}