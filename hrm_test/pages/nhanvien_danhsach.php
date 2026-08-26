                <!-- Table Read -->
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
                            require "config/db.php";
                            $sql = "SELECT * FROM nhanvien"; // Câu query
                            $result = $conn->query($sql);
                            if ($result->num_rows > 0) {
                                // Output data of each row
                                while($row = $result->fetch_assoc()) {
                                    // echo "id: " . $row["id"]. " - Name: " . $row["msnv"]. " " . $row["hoten"]. "<br>";
                                
                        ?>
                               
                            <tr>
                                <th scope="row"><?=$row["id"];?></th>
                                <td><?=$row["msnv"];?></td>
                                <td><?=$row["hoten"];?></td>
                                <td><?=$row["ngayvao"];?></td>
                                <td>
                                    <a href="pages/sua.php?id=edit_id=<?=$row["id"];?>" class="btn btn-outline-info">Sửa</a>
                                    <a href="pages/xoa.php?id=<?=$row["id"];?>" class="btn btn-outline-danger">Xóa</a>
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