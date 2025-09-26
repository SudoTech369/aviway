<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Fetch single product safely ---
$product = [];
$inv = [];
$fileO = [];
$img = '';

if (isset($_GET['id'])) {
    $stmt = $conn->prepare("
        SELECT p.*, b.name as bname, c.category 
        FROM products p
        INNER JOIN brands b ON p.brand_id = b.id
        INNER JOIN categories c ON p.category_id = c.id
        WHERE md5(p.id) = ?
    ");
    $stmt->bind_param("s", $_GET['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        foreach ($product as $k => $v) {
            $product[$k] = stripslashes($v);
        }

        $id = $product['id'];
        $upload_path = base_app . '/uploads/product_' . $id;

        if (is_dir($upload_path)) {
            $fileO = array_values(array_diff(scandir($upload_path), ['.', '..']));
            if (isset($fileO[0])) {
                $img = 'uploads/product_' . $id . '/' . $fileO[0];
            }
        }

        // --- Inventory ---
        $inventory = $conn->query("SELECT * FROM inventory WHERE product_id = " . (int)$id . " ORDER BY variant ASC");
        while ($ir = $inventory->fetch_assoc()) {
            $ir['price'] = format_num($ir['price']);
            $ir['stock'] = $ir['quantity'];
            $soldQ = $conn->query("
                SELECT SUM(quantity) 
                FROM order_list 
                WHERE inventory_id = '{$ir['id']}' 
                  AND order_id IN (SELECT order_id FROM sales)
            ")->fetch_array()[0];
            $sold = $soldQ > 0 ? $soldQ : 0;
            $ir['stock'] = $ir['stock'] - $sold;
            $inv[] = $ir;
        }
    }
}
?>
<style>
    .variant-item.active {
        border-color: var(--pink) !important;
    }
    .variant-item {
        cursor: pointer !important;
    }
</style>

<section class="py-5">
    <div class="container px-4 px-lg-5 my-5">
        <div class="row gx-4 gx-lg-5 align-items-center">
            <div class="col-md-6">
                <img class="card-img-top mb-5 mb-md-0 border border-dark" loading="lazy"
                     id="display-img"
                     src="<?php echo validate_image($img); ?>" alt="..." />

                <?php if (!empty($fileO)): ?>
                <div class="mt-2 row gx-2 gx-lg-3 row-cols-4 row-cols-md-3 row-cols-xl-4 justify-content-start">
                    <?php foreach ($fileO as $k => $fimg): ?>
                        <div class="col">
                            <a href="javascript:void(0)" class="view-image <?php echo $k === 0 ? 'active' : ''; ?>">
                                <img src="<?php echo validate_image('uploads/product_' . $id . '/' . $fimg); ?>"
                                     loading="lazy" class="img-thumbnail" alt="">
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="col-md-6">
                <h1 class="display-5 fw-bolder border-bottom border-primary pb-1">
                    <?php echo $product['name'] ?? ''; ?>
                </h1>
                <p class="m-0"><small>Brand: <?php echo $product['bname'] ?? ''; ?></small></p>

                <div class="fs-5 mb-5">
                    &#8369; <span id="price">
                        <?php echo isset($inv[0]['price']) ? $inv[0]['price'] : '--'; ?>
                    </span><br>
                    <span><small><span class="text-muted">Available Stock:</span>
                        <span id="avail"><?php echo isset($inv[0]['stock']) ? $inv[0]['stock'] : '--'; ?></span></small></span>
                    <h5></h5>
                    <?php $active = false; foreach ($inv as $k => $v): ?>
                        <span class="variant-item border rounded-pill bg-gradient-light mr-2 text-xs px-3 
                            <?= !$active ? 'active' : '' ?>" data-key="<?= $k ?>">
                            <?= htmlspecialchars($v['variant']) ?>
                        </span>
                    <?php $active = true; endforeach; ?>
                </div>

                <form action="" id="add-cart">
                    <div class="d-flex">
                        <input type="hidden" name="price" value="<?php echo $inv[0]['price'] ?? 0; ?>">
                        <input type="hidden" name="inventory_id" value="<?php echo $inv[0]['id'] ?? ''; ?>">
                        <input class="form-control text-center me-3" id="inputQuantity" type="number" value="1"
                               style="max-width: 3rem" name="quantity" />
                        <button class="btn btn-outline-light flex-shrink-0" type="submit">
                            <i class="bi-cart-fill me-1"></i>
                            Add to cart
                        </button>
                    </div>
                </form>

                <p class="lead"><?php echo isset($product['specs']) ? stripslashes(html_entity_decode($product['specs'])) : ''; ?></p>
            </div>
        </div>
    </div>
</section>

<!-- Related items -->
<section class="py-5 bg-light">
    <div class="container px-4 px-lg-5 mt-5">
        <h2 class="fw-bolder mb-4">Related products</h2>
        <div class="row gx-4 gx-lg-5 row-cols-1 row-cols-md-3 row-cols-xl-4 justify-content-center">
        <?php
        if (!empty($product)) {
            $category_id = $product['category_id'];
            $brand_id    = $product['brand_id'];

            $rel = $conn->prepare("
                SELECT p.*, b.name as bname, c.category
                FROM products p
                INNER JOIN brands b ON p.brand_id = b.id
                INNER JOIN categories c ON p.category_id = c.id
                WHERE p.status = 1
                  AND (p.category_id = ? OR p.brand_id = ?)
                  AND p.id != ?
                ORDER BY RAND() LIMIT 4
            ");
            $rel->bind_param('iii', $category_id, $brand_id, $id);
            $rel->execute();
            $rproducts = $rel->get_result();
            while ($row = $rproducts->fetch_assoc()):
                foreach ($row as $k => $v) {
                    $row[$k] = trim(stripslashes($v));
                }
                $upload_path = base_app . '/uploads/product_' . $row['id'];
                $rimg = '';
                if (is_dir($upload_path)) {
                    $fileRel = array_values(array_diff(scandir($upload_path), ['.', '..']));
                    if (isset($fileRel[0])) {
                        $rimg = 'uploads/product_' . $row['id'] . '/' . $fileRel[0];
                    }
                }
                $rinventory = $conn->query("SELECT DISTINCT(price) FROM inventory WHERE product_id = " . (int)$row['id'] . " ORDER BY price ASC");
                $rinv = [];
                while ($ir = $rinventory->fetch_assoc()) {
                    $rinv[] = format_num($ir['price']);
                }
                $price = '';
                if (isset($rinv[0])) {
                    $price .= $rinv[0];
                    if (count($rinv) > 1) {
                        $price .= ' ~ ' . $rinv[count($rinv) - 1];
                    }
                }
        ?>
            <div class="col mb-5">
                <a class="card product-item text-reset text-decoration-none"
                   href=".?p=view_product&id=<?php echo md5($row['id']); ?>">
                    <div class="overflow-hidden shadow product-holder">
                        <img class="card-img-top w-100 product-cover" src="<?php echo validate_image($rimg); ?>" alt="..." />
                    </div>
                    <div class="card-body p-4">
                        <div>
                            <h5 class="fw-bolder"><?php echo $row['name']; ?></h5>
                            <span><b class="text-muted">Price: </b><?php echo $price; ?></span>
                            <p class="m-0"><small>Brand: <?php echo $row['bname']; ?></small></p>
                            <p class="m-0"><small><span class="text-muted">Category:</span> <?php echo $row['category']; ?></small></p>
                        </div>
                    </div>
                </a>
            </div>
        <?php endwhile; } ?>
        </div>
    </div>
</section>

<script>
var inv = <?php echo json_encode($inv) ?>;

$(function(){
  $('.view-image').click(function(){
    var _img = $(this).find('img').attr('src');
    $('#display-img').attr('src',_img);
    $('.view-image').removeClass("active")
    $(this).addClass("active")
  });

  $('.variant-item').click(function(){
    var k = $(this).data('key');
    $('.variant-item').removeClass("active")
    $(this).addClass("active")
    if(inv[k]){
      $('#price').text(inv[k].price)
      $('[name="price"]').val(inv[k].price)
      $('#avail').text(inv[k].stock)
      $('[name="inventory_id"]').val(inv[k].id)
      $('#inputQuantity').val(1)
    } else {
      alert_toast("Variant not found",'error')
    }
  });

  $('#add-cart').submit(function(e){
    e.preventDefault();

    // Login guard from server-side (this was already in your page)
    if(<?= ($_settings->userdata('id') > 0 || $_settings->userdata('login_type') == 2) ? 1 : 0 ?> != 1){
      uni_modal("","login.php");
      return false;
    }

    start_loader();
    $.ajax({
      url: 'classes/Master.php?f=add_to_cart',
      method: 'POST',
      data: $(this).serialize(),
      success: function(resp){
        end_loader();
        // resp should already be JSON object if server sets header/content correctly.
        // But sometimes jQuery returns string; handle both.
        if(typeof resp === 'string'){
          try { resp = JSON.parse(resp); } catch(e){
            console.error('Non-JSON response:', resp);
            alert_toast('Server error: invalid response','error');
            return;
          }
        }
        console.log('add_to_cart response:', resp);
        if(resp.status === 'success'){
          alert_toast("Product added to cart.",'success');
          $('#cart-count').text(resp.cart_count);
        } else {
          alert_toast(resp.msg || 'An error occurred while adding to cart.','error');
        }
      },
      error: function(xhr){
        end_loader();
        console.error('AJAX error:', xhr.responseText);
        alert_toast('Network/server error. Check console for details.','error');
      }
    });
  });
});
</script>
