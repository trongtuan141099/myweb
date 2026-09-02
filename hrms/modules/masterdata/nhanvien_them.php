<!-- Form Create -->
<div class="row">
    <form class="row" method="post">
        <div class="col-3">
            <div class="input-group flex-nowrap">
                <span class="input-group-text" id="addon-wrapping">Msnv</span>
                <input type="text" name="msnv" class="form-control" placeholder="MSNV" aria-label="Username" aria-describedby="addon-wrapping" value="">
            </div>
        </div>
        <div class="col-3">
            <div class="input-group flex-nowrap">
                <span class="input-group-text" id="addon-wrapping">Name</span>
                <input type="text" name="hoten" class="form-control" placeholder="Họ và Tên" aria-label="Username" aria-describedby="addon-wrapping" value="">
            </div>
        </div>
        <div class="col-3">
            <div class="input-group flex-nowrap">
                <span class="input-group-text" id="addon-wrapping">Date</span>
                <input type="date" name="ngayvao" class="form-control" placeholder="Ngày vào Cty" aria-label="Username" aria-describedby="addon-wrapping" value="">
            </div>
        </div>
        <div class="col-3">
            <button type="submit" formaction="pages/themmoi.php" class="btn btn-primary">Submit</button>
            <button type="submit" formaction="pages/capnhat.php" class="btn btn-info">Update</button>
        </div>
    </form>
</div>