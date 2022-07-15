<?php
/**
 * Plugin Name:       API Bộ Công Thương
 * Description:       API Bộ Công Thương
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Version:           1.1.0
 * Author:            Deltalabs
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       Deltalabs
 */

function gt_set_post_view() {
    if(!get_option('dtl_view_count'))  add_option('dtl_view_count',1);
    $count = (int)get_option('dtl_view_count');
    $count++;
    update_option('dtl_view_count',$count);
}
add_action( 'wp', 'gt_set_post_view' );
function soLuongTruyCap(){
    if(get_option( 'dtl_view_count' )){
        return (int) get_option( 'dtl_view_count' );
    }else{
        return 0;
    }
}
function soNguoiBan(){
    $user_query = new WP_User_Query( array( 'role' => 'customer', ) );
    return (int) $user_query->get_total();
}
function soNguoiBanMoi(){
    $user_query = new WP_User_Query( array(
        'role' => 'customer',
        'date_query'    => array(

            'after'     => date('Y').'-01-01 00:00:00',
            'inclusive' => true,

        ),
    ));
    return (int) $user_query->get_total();
}
function tongSoSanPham(){
    return (int) wp_count_posts('product')->publish;
}
function soSanPhamMoi(){
    $query = new WP_Query(array(
        'post_type'      => 'product',
        'date_query'    => array(
            'after'     => date('Y').'-01-01',
            'inclusive' => true,
        ),
    ));
    return (int) $query->found_posts;
}
function soLuongGiaoDich(){
    return tongSoDonHangThanhCong() + tongSoDonHangKhongThanhCong();
}
function tongSoDonHangThanhCong(){
    $query = new WP_Query(array(
        'post_type'      => 'shop_order',
        'post_status' => array('wc-completed'),
        'date_query'    => array(
            'after'     => date('Y').'-01-01',
            'inclusive' => true,
        ),
    ));
    return (int) $query->found_posts;
}
function tongSoDonHangKhongThanhCong(){
    $query = new WP_Query(array(
        'post_type'      => 'shop_order',
        'post_status' => array('wc-cancelled'),
        'date_query'    => array(
            'after'     => date('Y').'-01-01',
            'inclusive' => true,
        ),
    ));
    return (int) $query->found_posts;
}

function tongGiaTriGiaoDich(){

    $args = array(
        'post_type'   => 'shop_order',
        'post_status' => array('wc-completed'),
        'numberposts' => -1
    );

    $customer_orders = get_posts($args);
    $total = 0;

    if (!empty($customer_orders)) {
        foreach ($customer_orders as $customer_order){

            $order = wc_get_order($customer_order->ID);
            $total += $order->total;
        }

        return (int) $total;
    }
    else {
        return (int) 0;
    }
}

function at_rest_baocao_endpoint($request)
{
    $data = [];

    if($request->get_param('UserName') === get_option('dtl_api_username') && $request->get_param('PassWord') === get_option('dtl_api_password')){
        $data = array(
            'soLuongTruyCap' => soLuongTruyCap(),
            'soNguoiBan' => soNguoiBan(),
            'soNguoiBanMoi' => soNguoiBanMoi(),
            'tongSoSanPham' => tongSoSanPham(),
            'soSanPhamMoi' => soSanPhamMoi(),
            'soLuongGiaoDich' => soLuongGiaoDich(),
            'tongSoDonHangThanhCong' => tongSoDonHangThanhCong(),
            'tongSoDonHangKhongThanhCong' => tongSoDonHangKhongThanhCong(),
            'tongGiaTriGiaoDich' => tongGiaTriGiaoDich(),
        );
    }
    return new WP_REST_Response($data);
}
function at_rest_init()
{
    // route url: domain.com/wp-json/$namespace/$route
    $namespace = 'v1';
    $route     = 'baocao';

    register_rest_route($namespace, $route, array(
        'methods'   => WP_REST_Server::CREATABLE,
        'callback'  => 'at_rest_baocao_endpoint',
        'permission_callback' => '__return_true'
    ));
}
add_action('rest_api_init', 'at_rest_init');

// create custom plugin settings menu
add_action('admin_menu', 'my_cool_plugin_create_menu');

function my_cool_plugin_create_menu() {

    add_menu_page('API Bộ Công Thương', 'API Bộ Công Thương', 'administrator', __FILE__, 'api_bo_cong_thuong_page' , 'dashicons-awards' );

    add_action( 'admin_init', 'register_api_bo_cong_thuong_plugin_settings' );
}


function register_api_bo_cong_thuong_plugin_settings() {
    //register our settings
    register_setting( 'api-bo-cong-thuong-settings-group', 'dtl_api_username');
    register_setting( 'api-bo-cong-thuong-settings-group', 'dtl_api_password');
}

function api_bo_cong_thuong_page() {
    ?>
    <div class="wrap">
        <h1>API Bộ Công Thương</h1>
        <form autocomplete="off" method="post" action="options.php">
            <?php settings_fields( 'api-bo-cong-thuong-settings-group' ); ?>
            <?php do_settings_sections( 'api-bo-cong-thuong-settings-group' ); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">API Username</th>
                    <td><input autocomplete="off" type="text" name="dtl_api_username" value="<?php echo esc_attr( get_option('dtl_api_username') ); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">API Password</th>
                    <td><input autocomplete="new-password" id='password' type="password" name="dtl_api_password" value="<?php echo esc_attr( get_option('dtl_api_password') ); ?>" />
                        <input type='checkbox' id='toggle' value='0' onchange='togglePassword(this);'>&nbsp; <span id='toggleText'>Show</span>
                    </td>
                    </td>
                </tr>
            </table>
            <p><b>Link API: </b><a href="<?php echo get_site_url(null,'/wp-json/v1/baocao') ?>" target="_blank"><?php echo get_site_url(null,'/wp-json/v1/baocao') ?></a></p>
            <p>Sử dụng phương thức POST và Content-Type: application/json để truy cập API</p>
            <b>Headers:</b>
            <pre>
                Content-Type:application/json
            </pre>
            <b>Body:</b>
            <pre>
                {
                    "UserName": "<?php echo esc_attr( get_option('dtl_api_username') ); ?>",
                    "PassWord": "<?php echo esc_attr( get_option('dtl_api_password') ); ?>"
                }
            </pre>
            <?php submit_button(); ?>

        </form>
        <script>
            function togglePassword(res){
                var checked = res.checked;
                if(checked){
                    document.getElementById("password").type = 'text';
                    document.getElementById("toggleText").textContent= "Hide";
                }else{
                    document.getElementById("password").type = 'password';
                    document.getElementById("toggleText").textContent= "Show";
                }
            }
        </script>
    </div>
<?php } ?>
