<div class="row">
    <table class="table">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">MSNV</th>
                <th scope="col">Họ và tên</th>
                <th scope="col">Ngày vào Cty</th>
                <th scope="col">Hành động</th>
            </tr>
        </thead>
        <tbody>
        <?php 
            // require "../config/db.php";
            $sql = "SELECT * FROM nhanvien"; // Câu query
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                // Output data of each row
                $stt=0;
                while($row = $result->fetch_assoc()) {
                    // echo "id: " . $row["id"]. " - Name: " . $row["msnv"]. " " . $row["hoten"]. "<br>";
                    $stt++;
        ?>
                
            <tr>
                <th scope="row"><?=$stt;?></th>
                <td><?=$row["msnv"];?></td>
                <td><?=$row["hoten"];?></td>
                <td><?=$row["ngayvao"];?></td>
                <td>
                    <a href="index.php?edit_id=<?=$row["id"];?>">✒️</a>
                    <a href="index.php?del_id=<?=$row["id"];?>">❌</a>
                </td>
            </tr>

        <?php
                }
            }
            $conn->close();
        ?>
            
        </tbody>
    </table>
</div>