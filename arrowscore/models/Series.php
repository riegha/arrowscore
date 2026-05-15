<?php
class Series {
    private $conn;
    public function __construct() {
        $this->conn = (new Database())->getConnection();
    }

    public function getPointsRule($competition_id) {
        $stmt = $this->conn->prepare("SELECT `rank`, points FROM series_points_rules WHERE competition_id = ? ORDER BY `rank`");
        $stmt->execute([$competition_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStandings($competition_id, $category_id = null) {
        $sql = "SELECT id, name FROM rounds WHERE competition_id = ? AND status = 'finished'";
        $params = [$competition_id];
        if ($category_id !== null && $category_id > 0) {
            $sql .= " AND category_id = ?";
            $params[] = (int)$category_id;
        }
        $sql .= " ORDER BY id";
        $stmtRounds = $this->conn->prepare($sql);
        $stmtRounds->execute($params);
        $rounds = $stmtRounds->fetchAll(PDO::FETCH_ASSOC);

        $standings = [];
        $scoreModel = new Score();
        foreach ($rounds as $round) {
            $results = $scoreModel->getLiveScores($round['id']);
            $rank = 1;
            foreach ($results as $res) {
                $pid = $res['id'];
                if (!isset($standings[$pid])) {
                    $standings[$pid] = [
                        'name'        => $res['name'],
                        'club'        => $res['club_name'] ?? 'Perorangan',
                        'points'      => 0,
                        'total_score' => 0,
                        'rounds'      => []      // detail per babak
                    ];
                }
                $point = $this->getPointForRank($competition_id, $rank);
                $standings[$pid]['points'] += $point;
                $standings[$pid]['total_score'] += $res['total_score'];
                // Simpan data per babak
                $standings[$pid]['rounds'][$round['id']] = [
                    'round_name'  => $round['name'],
                    'total_score' => $res['total_score'],
                    'x_count'     => $res['x_count'],
                    'rank'        => $rank
                ];
                $rank++;
            }
        }
        // Urutkan berdasarkan poin tertinggi dan score tertinggi
		usort($standings, function($a, $b) {
			if ($b['points'] != $a['points']) {
				return $b['points'] - $a['points'];
			}
			return $b['total_score'] - $a['total_score'];
		});
        return $standings;
    }

    public function getClubStandings($competition_id, $category_id = null) {
        $individual = $this->getStandings($competition_id, $category_id);
        $clubs = [];
        foreach ($individual as $data) {
            $club = $data['club'] ?: 'Perorangan';
            if (!isset($clubs[$club])) $clubs[$club] = 0;
            $clubs[$club] += $data['points'];
        }
        arsort($clubs);
        return $clubs;
    }

    private function getPointForRank($competition_id, $rank) {
        $stmt = $this->conn->prepare("SELECT points FROM series_points_rules WHERE competition_id = ? AND `rank` = ?");
        $stmt->execute([$competition_id, $rank]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ? (int)$res['points'] : 0;
    }
}