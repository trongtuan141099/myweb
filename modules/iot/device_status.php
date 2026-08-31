<div class="container">
    <div class="card">
        <h2>📡 Trạng Thái Thiết Bị Hiện Tại</h2>
        <table>
            <tr>
                <th>Mã Thiết Bị</th>
                <th>Địa Chỉ IP ESP32</th>
                <th>Trạng Thái</th>
                <th>Cập Nhật Lần Cuối</th>
            </tr>
            <?php
            $result = $conn->query("SELECT * FROM device_status");
            while($row = $result->fetch_assoc()) {
                echo "<tr>
                    <td><b>{$row['device_id']}</b></td>
                    <td><a href='http://{$row['ip_address']}' target='_blank'>{$row['ip_address']}</a></td>
                    <td><span class='badge {$row['status']}'>{$row['status']}</span></td>
                    <td>{$row['last_update']}</td>
                </tr>";
            }
            ?>
        </table>
    </div>

    <div class="card">
        <h2>📜 Lịch Sử Hoạt Động (100 bản ghi mới nhất)</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Mã Thiết Bị</th>
                <th>Trạng Thái</th>
                <th>Thời Gian Change</th>
            </tr>
            <?php
            $history = $conn->query("SELECT * FROM device_history ORDER BY id DESC LIMIT 100");
            while($row = $history->fetch_assoc()) {
                echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['device_id']}</td>
                    <td><span class='badge {$row['status']}'>{$row['status']}</span></td>
                    <td>{$row['timestamp']}</td>
                </tr>";
            }
            ?>
        </table>
    </div>
</div>

<style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7f6; margin: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        h2 { color: #333; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #007bff; color: white; }
        .badge { padding: 5px 10px; border-radius: 4px; font-weight: bold; color: #fff; }
        .ON { background-color: #28a745; }
        .OFF { background-color: #dc3545; }
    </style>