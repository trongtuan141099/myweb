                <!-- Form Create -->
                <form  method="post">
                    <div class="row">
                        <div class="col-3">
                            <div class="input-group flex-nowrap">
                                <span class="input-group-text" id="addon-wrapping">Mã số nhân viên</span>
                                <input type="text" name="msnv" class="form-control" placeholder="Username" aria-label="Username" aria-describedby="addon-wrapping">
                            </div>
                        </div>
                        
                        <div class="col-3">
                            <div class="input-group flex-nowrap">
                                <span class="input-group-text" id="addon-wrapping">Tên</span>
                                <input type="text" name="ten" class="form-control" placeholder="Username" aria-label="Username" aria-describedby="addon-wrapping">
                            </div>
                        </div>

                        <div class="col-3">
                            <div class="input-group flex-nowrap">
                                <span class="input-group-text" id="addon-wrapping">Ngày vào công ty</span>
                                <input type="date" name="ngayvao" class="form-control" placeholder="Username" aria-label="Username" aria-describedby="addon-wrapping">
                            </div>
                        </div>
                        
                        <div class="col-3 d-flex gap-2 align-items-start">
                            <button type="submit" class="btn btn-primary text-nowrap" formaction="pages/themmoi.php">Thêm mới</button>
                            <button type="submit" class="btn btn-primary text-nowrap" formaction="pages/capnhat.php">Cập nhật</button>
                        </div>
                </form>