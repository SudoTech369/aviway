<style>
    /* Carousel */
    .carousel-item > img {
        object-fit: cover !important;
    }
    #carouselExampleControls .carousel-inner {
        height: 380px !important;
        width: 960px !important;
        margin: auto;
    }

    /* Product card sizing */
    .product-item {
        display: flex;
        flex-direction: column;
        max-width: 400px;
        margin: auto;
        border-radius: 35px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.2);
        overflow: hidden;
        background: #fff;
    }

    /* Image fills top part */
    .product-item .product-holder {
        flex: 1;
        width: 100%;
        overflow: hidden;
        position: relative;
    }

    .product-item .product-holder img.product-cover {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.4s ease;
        display: block;
    }

    .product-item:hover .product-cover {
        transform: scale(1.08);
    }

    /* Text below */
    .product-item .card-body {
        padding: 5px;
        text-align: left;
        font-size: 1rem;
        background: #fff;
    }

    .product-item .card-body h5 {
        font-size: 1.2rem;
        font-weight: bold;
        margin-bottom: 6px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .product-item .card-body span,
    .product-item .card-body p {
        margin: 0;
        color: #333;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .product-item .card-body span b {
        color: #000;
    }

    /* Toolbar for grid filter */
    .grid-toolbar {
        text-align: center;
        margin: 20px 0;
    }
    .grid-toolbar button {
        padding: 6px 12px;
        margin: 0 5px;
        border: none;
        border-radius: 6px;
        background: #444;
        color: #fff;
        cursor: pointer;
        transition: 0.2s;
        font-size: 1.2rem;
    }
    .grid-toolbar button:hover {
        background: #000;
    }
    .grid-toolbar button.active {
        background: #007bff;
    }
</style>


<?php 
$brands = isset($_GET['b']) ? json_decode(urldecode($_GET['b'])) : array();
?>
<section class="py-0">
    <div class="container">
        <div class="row">
            <!-- Main content -->
            <div class="col-lg-12 py-2">
                <!-- Carousel -->
                <div class="row">
                    <div class="col-md-12">
                        <div id="carouselExampleControls" class="carousel slide bg-dark" data-ride="carousel">
                            <div class="carousel-inner">
                                <?php 
                                $upload_path = "uploads/banner";
                                if(is_dir(base_app.$upload_path)): 
                                    $file = scandir(base_app.$upload_path);
                                    $_i = 0;
                                    foreach($file as $img):
                                        if(in_array($img, array('.', '..'))) continue;
                                        $_i++;
                                ?>
                                <div class="carousel-item h-100 <?php echo $_i == 1 ? "active" : '' ?>">
                                    <img src="<?php echo validate_image($upload_path.'/'.$img) ?>" class="d-block w-100 h-100" alt="<?php echo $img ?>">
                                </div>
                                <?php endforeach; endif; ?>
                            </div>
                            <button class="carousel-control-prev" type="button" data-target="#carouselExampleControls" data-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-target="#carouselExampleControls" data-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Toolbar filter -->
                <div class="grid-toolbar">
                    <span></span>
                    <button id="btnGrid2" onclick="setGrid(2)"><i class="bi bi-grid-fill"></i></button>
                    <button id="btnGrid3" onclick="setGrid(3)" class="active"><i class="bi bi-grid-3x3-gap-fill"></i></button>
                    <button id="btnGrid4" onclick="setGrid(4)"><i class="bi bi-grid-3x3-gap"></i></button>
                </div>

                <!-- Products grid -->
                <div class="container px-4 px-lg-5 mt-3">
                    <div id="productsRow" class="row gx-4 gx-lg-4 row-cols-1 row-cols-md-3">
                        <?php 
                        $where = "";
                        if(count($brands) > 0)
                            $where = " AND p.brand_id IN (".implode(",", $brands).")";
                        
                        $products = $conn->query("SELECT p.*, b.name as bname, c.category 
                            FROM products p 
                            INNER JOIN brands b ON p.brand_id = b.id 
                            INNER JOIN categories c ON p.category_id = c.id 
                            WHERE p.status = 1 {$where} 
                            ORDER BY RAND()");
                        
                        // Fetch all into array
                        $product_list = [];
                        while($row = $products->fetch_assoc()){
                            $product_list[] = $row;
                        }

                        // Move last to front
                        if(!empty($product_list)){
                            $last = array_pop($product_list);
                            array_unshift($product_list, $last);
                        }

                        // Render
                        foreach($product_list as $row):
                            $upload_path = base_app.'/uploads/product_'.$row['id'];
                            $img = "";
                            if(is_dir($upload_path)){
                                $fileO = scandir($upload_path);
                                if(isset($fileO[2]))
                                    $img = "uploads/product_".$row['id']."/".$fileO[2];
                            }

                            foreach($row as $k => $v){
                                $row[$k] = trim(stripslashes($v));
                            }

                            $inventory = $conn->query("SELECT DISTINCT(price) FROM inventory WHERE product_id = ".$row['id']." ORDER BY price ASC");
                            $inv = array();
                            while($ir = $inventory->fetch_assoc()){
                                $inv[] = format_num($ir['price']);
                            }

                            $price = '';
                            if(isset($inv[0])) $price .= $inv[0];
                            if(count($inv) > 1) $price .= " ~ ".$inv[count($inv) - 1];
                        ?>
                        <div class="col mb-5">
                            <a class="card product-item text-reset text-decoration-none" href=".?p=view_product&id=<?php echo md5($row['id']) ?>">
                                <div class="product-holder">
                                    <img class="product-cover" src="<?php echo validate_image($img) ?>" alt="Product Image" />
                                </div>
                                <div class="card-body">
                                    <h5><?php echo $row['name'] ?></h5>
                                    <span><b>Price:</b> <?php echo $row['price'] ?></span>
                                    <p class="m-0"><small><b>Brand:</b> <?php echo $row['bname'] ?></small></p>
                                    <p class="m-0"><small><b>Category:</b> <?php echo $row['category'] ?></small></p>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Bootstrap icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
<script>
    function setGrid(cols) {
        const row = document.getElementById("productsRow");
        row.className = `row gx-4 gx-lg-4 row-cols-1 row-cols-md-${cols}`;

        // toggle active button
        document.querySelectorAll(".grid-toolbar button").forEach(btn => btn.classList.remove("active"));
        document.getElementById("btnGrid" + cols).classList.add("active");
    }
</script>
