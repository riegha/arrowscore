<?php
class TestController {
    public function show($id) {
        echo "Test berhasil! ID: " . htmlspecialchars($id);
    }
}