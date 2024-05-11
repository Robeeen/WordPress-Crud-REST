<?php
/*
 * Plugin Name: Custom Table
 * Description: This plugin provides functionality for adding and displaying data in a custom table. It also includes search functionality & custom endpoint to inser and 
 * Version: 1.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * Author: Shams Khan
 * Author URI: https://shamskhan.com
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:
 * Text Domain: custom-table
 * Domain Path: /languages/asset/
 */

// Exit if accessed directly
 if (!defined('ABSPATH')){
    exit;
  }

//adding bootstrap for styling.
function my_scripts() {
    wp_enqueue_style('bootstrap4', 'https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css');
}
add_action( 'wp_enqueue_scripts', 'my_scripts' );

//Activation of plugin
register_activation_hook( __FILE__, 'maybe_create_my_table');

//setup table for api data in Database
function maybe_create_my_table(){
    global $wpdb;
    $table_name = $wpdb->prefix . 'form_submission';
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar (100) NOT NULL,
        email varchar (100) NOT NULL,
        PRIMARY KEY (id)
        )";    
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}

//Creating shortcodes [my_form] and [my_list] to put on page/post
add_action( 'init', 'my_init' );
function my_init() {
    add_shortcode( 'my_form', 'my_shortcode_form' );
    add_shortcode( 'my_list', 'my_shortcode_list' );
}

//Callback fucntion for insert a Form on front-end
function my_shortcode_form(){
    $data = insert_data_to_my_table();
}

//To insert data from shortcode
function insert_data_to_my_table(){
   global $wpdb;
   $table_name = $wpdb->prefix . 'form_submission';
   if (isset($_POST['submit'])) {
     $name = sanitize_text_field($_POST['names']);
     $email = sanitize_email($_POST['email']);
     $format = array( '%s', '%s' );
     $wpdb->insert($table_name, array('name' => $name, 'email' => $email), $format );    
    }      
?>
<div class="card-body">
   <div class="wrap">
      <h3>Add Information</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Name</th>
                    <th>Email Address</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <form action="" method="post" id="simple-form">
                    <tr>
                        <td><input type="text" value="AUTO_GENERATED" disabled style="width:100%"></td>
                        <td><input type="text" required id="names" name="names" style="width:100%"></td>
                        <td><input type="text" required id="email" name="email" style="width:100%"></td>
                        <td><input type="submit" name="submit" type="submit" style="width:100%"></td>
                    </tr>
                </form>
            </tbody>
        </table>
    </div>
</div>
<?php
}

//Callback function to display data from shortcode
function my_shortcode_list() {
    global $wpdb;       
    $results = '<div class="card-body"><table class="table">
               <thead>
                <tr>
                    <th>id</th>
                    <th>name</th>
                    <th>email id</th>
                </tr>
            </thead>
        <tbody>';

        // sending query
        $WPQuery = $wpdb->get_results ("SELECT * FROM wp_form_submission");
            foreach ( $WPQuery as $print )   {
                $results .= "<tr>
                            <td>$print->id</td>
                            <td>$print->name</td>
                            <td>$print->email</td>
                        </tr>";
                    }
                $results .= "</tbody></table></div>";

    //Print results
    echo $results;
    
    //Call the Search function on table
 $search = get_my_table_data();
}

//Create serach function from table to display data.
function get_my_table_data(){
    global $wpdb;
   ?>
   <div class="card-body"><h3>Search on Table</h3>
   <form action="" method="get">
    <input type="text" required id="searchme" name="searchme" value='<?php if(isset($_GET['searchme'])){echo $_GET['searchme'];}?>' placeholder="Search">
    <input type="submit" id="search-item" name="search-item" value="search">
    </form>
    <table class="table">
            <thead>
                <tr>
                    <th>id</th>
                    <th>name</th>
                    <th>email id</th>
                </tr>
            </thead>
            <tbody>
    <?php
    
    $table_name = $wpdb->prefix . 'form_submission';
    if (isset($_GET['searchme'])) {
        $input = sanitize_text_field($_GET['searchme']);
        $sresults = $wpdb->get_results( "SELECT * FROM $table_name WHERE name LIKE '%$input%'" );
        if($sresults){
        foreach($sresults as $display){
            echo "<tr><td>$display->id</td>";
            echo "<td>$display->name</td>";
            echo "<td>$display->email</td>";
            echo "</tr>";
            echo "</tbody>";
            echo "</table></div>";  
        } }else echo "Not found";
    }else echo "<table>";
}

//Create and Register new Rest Route/endpoint/
add_action('rest_api_init', 'registering_routes');

//endpoint to display data from the table
 function registering_routes(){
    register_rest_route(
        'form_submission_route/v1',
        '/form-submission',
        array(
            'method' => 'GET',
            'callback' => 'form_sub_callback',
            'permission_callback' => '__return_true'
        )
    );

//endpoint to insert data to the table
    register_rest_route(
        'form_submission_route/v1',
        '/form-submission',
        array(
            'methods' => WP_REST_SERVER::CREATABLE,
            'callback' => 'form_get_callback',
            'permission_callback' => 'wp_check_permission'
        )
    );
}

//callback function for displaying data
function form_sub_callback(){
    global $wpdb;
    $table_name = $wpdb->prefix . 'form_submission';
    $results = $wpdb->get_results( "SELECT * FROM $table_name" );
    return $results;
}

//add security
function wp_check_permission(){
	return current_user_can('edit_posts');
}

//callback function to insert data to table
function form_get_callback($request){
    global $wpdb;
    $table_name = $wpdb->prefix . 'form_submission';

    $row = $wpdb->insert(
        $table_name,
        array(
            'name' => $request['name'],
            'email' => $request['email']
        )
    );
    return $row;
}

