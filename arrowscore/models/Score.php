<?php
class Score {
    private $conn;
    public function __construct() {
        $this->conn = (new Database())->getConnection();
    }

    public function getRoundById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM rounds WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAssignmentsByRound($round_id) {
        $stmt = $this->conn->prepare("
            SELECT pfa.*, p.name as participant_name, ft.number as target_number
            FROM participant_face_assignments pfa
            JOIN participants p ON pfa.participant_id = p.id
            JOIN face_targets ft ON pfa.face_target_id = ft.id
            WHERE pfa.round_id = ?
            ORDER BY ft.number, pfa.shooting_order
        ");
        $stmt->execute([$round_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getParticipantsByRound($round_id) {
        $stmt = $this->conn->prepare("
            SELECT DISTINCT p.id, p.name, c.name as club_name
            FROM participant_face_assignments pfa
            JOIN participants p ON pfa.participant_id = p.id
            LEFT JOIN clubs c ON p.club_id = c.id
            WHERE pfa.round_id = ?
            ORDER BY p.name
        ");
        $stmt->execute([$round_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRambahanByRound($round_id) {
        $stmt = $this->conn->prepare("SELECT * FROM rambahans WHERE round_id = ? ORDER BY number");
        $stmt->execute([$round_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getShotsByRambahanParticipant($rambahan_id, $participant_id) {
        $stmt = $this->conn->prepare("SELECT * FROM shots WHERE rambahan_id = ? AND participant_id = ? ORDER BY shot_number");
        $stmt->execute([$rambahan_id, $participant_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

	public function getParticipantsByTarget($face_target_id) {
		$stmt = $this->conn->prepare("
			SELECT p.id, p.name, c.name as club_name, pfa.shooting_order
			FROM participant_face_assignments pfa
			JOIN participants p ON pfa.participant_id = p.id
			LEFT JOIN clubs c ON p.club_id = c.id
			WHERE pfa.face_target_id = ?
			ORDER BY pfa.shooting_order
		");
		$stmt->execute([$face_target_id]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function createInputSession($round_id, $slug, $hashed_password, $targetIds) {
		// Pastikan targetIds tidak kosong dan merupakan array dari ID target valid
		if (empty($targetIds)) {
			throw new Exception("Tidak ada target yang dipilih.");
		}

		$this->conn->beginTransaction();
		try {
			$stmt = $this->conn->prepare("INSERT INTO input_sessions (round_id, unique_slug, hashed_password) VALUES (?, ?, ?)");
			$stmt->execute([$round_id, $slug, $hashed_password]);
			$session_id = $this->conn->lastInsertId();

			$order = 1;
			$stmt2 = $this->conn->prepare("INSERT INTO input_session_targets (input_session_id, face_target_id, display_order) VALUES (?, ?, ?)");
			foreach ($targetIds as $tid) {
				// Pastikan target ada di face_targets dan memiliki assignment (opsional)
				$stmt2->execute([$session_id, $tid, $order++]);
			}
			$this->conn->commit();
			return $session_id;
		} catch (Exception $e) {
			$this->conn->rollBack();
			die("Gagal membuat sesi input: " . $e->getMessage());
		}
	}

    public function getInputSessions($round_id) {
        $stmt = $this->conn->prepare("SELECT * FROM input_sessions WHERE round_id = ?");
        $stmt->execute([$round_id]);
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($sessions as &$s) {
            $stmtT = $this->conn->prepare("SELECT ft.number FROM input_session_targets ist JOIN face_targets ft ON ist.face_target_id = ft.id WHERE ist.input_session_id = ?");
            $stmtT->execute([$s['id']]);
            $s['targets'] = $stmtT->fetchAll(PDO::FETCH_COLUMN);
        }
        return $sessions;
    }

    public function getSessionBySlug($slug) {
        $stmt = $this->conn->prepare("SELECT * FROM input_sessions WHERE unique_slug = ?");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

	public function getTargetsBySession($session_id) {
		$stmt = $this->conn->prepare("
			SELECT ft.*, fc.label as cushion_label
			FROM input_session_targets ist
			JOIN face_targets ft ON ist.face_target_id = ft.id
			LEFT JOIN face_cushions fc ON ft.cushion_id = fc.id
			WHERE ist.input_session_id = ?
			ORDER BY ist.display_order
		");
		$stmt->execute([$session_id]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

    public function saveShot($rambahan_id, $participant_id, $shot_number, $score, $isX) {
        $check = $this->conn->prepare("SELECT id FROM shots WHERE rambahan_id = ? AND participant_id = ? AND shot_number = ?");
        $check->execute([$rambahan_id, $participant_id, $shot_number]);
        if ($existing = $check->fetch()) {
            $upd = $this->conn->prepare("UPDATE shots SET score = ?, is_x = ? WHERE id = ?");
            $upd->execute([$score, $isX, $existing['id']]);
        } else {
            $ins = $this->conn->prepare("INSERT INTO shots (rambahan_id, participant_id, shot_number, score, is_x) VALUES (?, ?, ?, ?, ?)");
            $ins->execute([$rambahan_id, $participant_id, $shot_number, $score, $isX]);
        }
    }

    public function updateShot($rambahan_id, $participant_id, $shot_number, $newScore, $newX, $adminId) {
        $stmt = $this->conn->prepare("SELECT id, score, is_x FROM shots WHERE rambahan_id = ? AND participant_id = ? AND shot_number = ?");
        $stmt->execute([$rambahan_id, $participant_id, $shot_number]);
        $old = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($old) {
            $upd = $this->conn->prepare("UPDATE shots SET score = ?, is_x = ? WHERE id = ?");
            $upd->execute([$newScore, $newX, $old['id']]);
            $log = $this->conn->prepare("INSERT INTO score_audit_logs (shot_id, old_score, new_score, old_is_x, new_is_x, changed_by) VALUES (?,?,?,?,?,?)");
            $log->execute([$old['id'], $old['score'], $newScore, $old['is_x'], $newX, $adminId]);
        }
    }

	public function getLiveScores($round_id) {
			// Ambil max_score_per_shot dari kategori babak ini
			$stmt = $this->conn->prepare("
				SELECT c.max_score_per_shot
				FROM rounds r
				JOIN categories c ON r.category_id = c.id
				WHERE r.id = ?
			");
			$stmt->execute([$round_id]);
			$category = $stmt->fetch(PDO::FETCH_ASSOC);
			$maxScore = $category ? (int)$category['max_score_per_shot'] : 10;

			// Bangun ekspresi SUM(CASE WHEN score = X THEN 1 ELSE 0 END) untuk setiap nilai dari maxScore hingga 0
			$caseExpressions = [];
			for ($i = $maxScore; $i >= 0; $i--) {
				$caseExpressions[] = "SUM(CASE WHEN s.score = $i THEN 1 ELSE 0 END) as count_$i";
			}
			$caseSql = implode(", ", $caseExpressions);

			// Bangun ORDER BY: total_score DESC, lalu x_count DESC, lalu count_maxScore DESC, count_maxScore-1 DESC, ..., count_0 DESC
			$orderBy = ["total_score DESC", "x_count DESC"];
			for ($i = $maxScore; $i >= 0; $i--) {
				$orderBy[] = "count_$i DESC";
			}
			$orderSql = implode(", ", $orderBy);

			$sql = "
				SELECT p.id, p.name, c.name as club_name,
					   SUM(s.score) as total_score,
					   SUM(CASE WHEN s.is_x = 1 THEN 1 ELSE 0 END) as x_count,
					   $caseSql
				FROM shots s
				JOIN participants p ON s.participant_id = p.id
				LEFT JOIN clubs c ON p.club_id = c.id
				JOIN rambahans r ON s.rambahan_id = r.id
				WHERE r.round_id = ?
				GROUP BY p.id, p.name, c.name
				ORDER BY $orderSql
			";

			$stmt = $this->conn->prepare($sql);
			$stmt->execute([$round_id]);
			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		}

    public function getFaceTargets($round_id) {
        $stmt = $this->conn->prepare("SELECT * FROM face_targets WHERE round_id = ? ORDER BY number");
        $stmt->execute([$round_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function clearAssignments($round_id) {
        $stmt = $this->conn->prepare("DELETE FROM participant_face_assignments WHERE round_id = ?");
        $stmt->execute([$round_id]);
    }

    public function addAssignment($round_id, $participant_id, $face_target_id, $shooting_order) {
        $stmt = $this->conn->prepare("INSERT INTO participant_face_assignments (round_id, participant_id, face_target_id, shooting_order) VALUES (?, ?, ?, ?)");
        $stmt->execute([$round_id, $participant_id, $face_target_id, $shooting_order]);
    }

	public function getParticipantsForRound($round_id, $allowedGender = null) {
		$sql = "SELECT DISTINCT p.id, p.name, p.gender, c.name as club_name
				FROM participants p
				JOIN round_participants rp ON p.id = rp.participant_id
				LEFT JOIN clubs c ON p.club_id = c.id
				WHERE rp.round_id = ?";
		$params = [$round_id];

		if ($allowedGender && $allowedGender !== 'semua') {
			$sql .= " AND p.gender = ?";
			$params[] = $allowedGender;
		}

		$sql .= " ORDER BY p.name";
		$stmt = $this->conn->prepare($sql);
		$stmt->execute($params);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

    public function getCushionsWithTargets($round_id) {
        $stmt = $this->conn->prepare("
            SELECT fc.id as cushion_id, fc.number as cushion_number, fc.label as cushion_label, fc.session,
                   ft.id as target_id, ft.number as target_number, ft.label as target_label
            FROM face_cushions fc
            LEFT JOIN face_targets ft ON fc.id = ft.cushion_id
            WHERE fc.round_id = ?
            ORDER BY fc.session, CAST(fc.number AS UNSIGNED), CAST(ft.number AS UNSIGNED)
        ");
        $stmt->execute([$round_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $cushions = [];
        foreach ($rows as $row) {
            $cid = $row['cushion_id'];
            if (!isset($cushions[$cid])) {
                $cushions[$cid] = [
                    'id' => $cid,
                    'number' => $row['cushion_number'],
                    'label' => $row['cushion_label'],
                    'session' => $row['session'],
                    'targets' => []
                ];
            }
            if ($row['target_id']) {
                $cushions[$cid]['targets'][$row['target_id']] = [
                    'id' => $row['target_id'],
                    'number' => $row['target_number'],
                    'label' => $row['target_label']
                ];
            }
        }
        return $cushions;
    }

    public function getCushionChampions($round_id, $cushion_id) {
        $stmt = $this->conn->prepare("
            SELECT p.id, p.name, c.name as club_name, 
                   SUM(s.score) as total_score
            FROM participant_face_assignments pfa
            JOIN face_targets ft ON pfa.face_target_id = ft.id
            JOIN participants p ON pfa.participant_id = p.id
            LEFT JOIN clubs c ON p.club_id = c.id
            JOIN rambahans r ON r.round_id = pfa.round_id
            JOIN shots s ON s.rambahan_id = r.id AND s.participant_id = p.id
            WHERE pfa.round_id = ? AND ft.cushion_id = ?
            GROUP BY p.id, p.name, c.name
            ORDER BY total_score DESC
        ");
        $stmt->execute([$round_id, $cushion_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTargetIdsWithInputSession($round_id) {
        $stmt = $this->conn->prepare("
            SELECT DISTINCT ist.face_target_id
            FROM input_session_targets ist
            JOIN input_sessions isess ON ist.input_session_id = isess.id
            WHERE isess.round_id = ?
        ");
        $stmt->execute([$round_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function lockSession($session_id) {
        $stmt = $this->conn->prepare("UPDATE input_sessions SET status = 'locked' WHERE id = ?");
        $stmt->execute([$session_id]);
    }

    public function isSessionLocked($session_id) {
        $stmt = $this->conn->prepare("SELECT status FROM input_sessions WHERE id = ?");
        $stmt->execute([$session_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row && $row['status'] === 'locked';
    }
}