<?php
class Competition {
    private $conn;
    public function __construct() {
        $this->conn = (new Database())->getConnection();
    }

    public function getAll() {
        $stmt = $this->conn->query("SELECT * FROM competitions ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM competitions WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getBySlug($slug) {
        $stmt = $this->conn->prepare("SELECT * FROM competitions WHERE public_link_slug = ?");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($name, $type) {
        $slug = substr(md5(uniqid()), 0, 8);
        $stmt = $this->conn->prepare("INSERT INTO competitions (name, type, public_link_slug) VALUES (?, ?, ?)");
        $stmt->execute([$name, $type, $slug]);
        return $this->conn->lastInsertId();
    }

    public function getCategories($competition_id) {
        $stmt = $this->conn->prepare("SELECT * FROM categories WHERE competition_id = ?");
        $stmt->execute([$competition_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addCategory($competition_id, $name, $max, $min) {
        $stmt = $this->conn->prepare("INSERT INTO categories (competition_id, name, max_score_per_shot, min_score_per_shot) VALUES (?, ?, ?, ?)");
        $stmt->execute([$competition_id, $name, $max, $min]);
    }

    public function getRounds($competition_id) {
        $stmt = $this->conn->prepare("SELECT r.*, c.name as category_name FROM rounds r JOIN categories c ON r.category_id = c.id WHERE r.competition_id = ? ORDER BY r.order ASC");
        $stmt->execute([$competition_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

	public function addRound($competition_id, $category_id, $name, $format, $shots, $rambahan, $face, $cushionCount, $allowedGender = 'semua', $shootingOrders = 2, $hasCushionChampion = 0) {
		$orderStmt = $this->conn->prepare("SELECT COUNT(*) FROM rounds WHERE competition_id = ?");
		$orderStmt->execute([$competition_id]);
		$order = $orderStmt->fetchColumn() + 1;

		$stmt = $this->conn->prepare("INSERT INTO rounds (competition_id, category_id, name, `order`, format, shots_per_rambahan, total_rambahan, face_target_count, cushion_count, allowed_gender, shooting_orders, has_cushion_champion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
		$stmt->execute([$competition_id, $category_id, $name, $order, $format, $shots, $rambahan, $face, $cushionCount, $allowedGender, $shootingOrders, $hasCushionChampion]);
		$round_id = $this->conn->lastInsertId();

		$targetPerCushion = intdiv($face, $cushionCount);
		$remainder = $face % $cushionCount;
		$targetLabels = range('A', 'Z');
		$globalTargetNum = 1;

		for ($c = 1; $c <= $cushionCount; $c++) {
			$cushionLabel = "Bantalan " . $c;
			$this->conn->prepare("INSERT INTO face_cushions (round_id, session, number, label) VALUES (?, 1, ?, ?)")->execute([$round_id, $c, $cushionLabel]);
			$cushionId = $this->conn->lastInsertId();

			$targetsForThisCushion = $targetPerCushion + ($remainder > 0 ? 1 : 0);
			if ($remainder > 0) $remainder--;

			for ($t = 0; $t < $targetsForThisCushion; $t++) {
				$label = "Target " . ($targetLabels[$t] ?? ($t + 1));
				$this->conn->prepare("INSERT INTO face_targets (round_id, cushion_id, number, label) VALUES (?, ?, ?, ?)")->execute([$round_id, $cushionId, $globalTargetNum, $label]);
				$globalTargetNum++;
			}
		}

		for ($i = 1; $i <= $rambahan; $i++) {
			$this->conn->prepare("INSERT INTO rambahans (round_id, number) VALUES (?, ?)")->execute([$round_id, $i]);
		}
	}

    public function getCategoryById($category_id) {
        $stmt = $this->conn->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

	public function getParticipantsByCategory($category_id) {
		$stmt = $this->conn->prepare("
			SELECT DISTINCT p.id, p.name, p.birth_date, p.address, p.gender, c.name as club_name
			FROM participants p
			JOIN competition_participants cp ON p.id = cp.participant_id 
				AND cp.category_id = ?
			LEFT JOIN clubs c ON p.club_id = c.id
			ORDER BY p.name ASC
		");
		$stmt->execute([$category_id]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

    public function addParticipantToCategory($competition_id, $participant_id, $category_id) {
        $stmt = $this->conn->prepare("INSERT INTO competition_participants (competition_id, participant_id, category_id) VALUES (?, ?, ?)");
        $stmt->execute([$competition_id, $participant_id, $category_id]);
    }
}