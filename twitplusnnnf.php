<?php
/*
Plugin Name: TwitPlusNNNF
Plugin URI: http://dnhints.com/plus/
Description: This plugin allows you to automatically twit your new/edited posts. Combined with url shortener <a href="http://nn.nf">nn.nf</a>.
Author: Dmitry Ulman
Version: 1.0.6
Author URI: http://dnhints.com/plus/
*/

/*  Copyright 2009  Dmitry Ulman  (email : dima.ulman@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define ('TWITPLUSNNNF_TWITTER_URL', 'http://www.twitter.com/');
define ('TWITPLUSNNNF_STORED_PASS', '**********');
define ('TWITPLUSNNNF_VERSION', '1.0.6');

add_action('admin_menu', 'tpnnnf_add_custom_box');
add_action('publish_post', 'tpnnnf_save_postdata', 1, 2);
add_action('wp_ajax_tpnnnf_testacc', 'tpnnnf_test_acc');
add_action('admin_notices', 'tpnnnf_notice');

function tpnnnf_notice(){
    $res = get_option('tpnnnf-result');
    if (!empty($res))
        echo '<div class="'.($res['error'] ? 'error' : 'updated').'"><p><b>TwitPlusNNNF:</b> '.$res['message'].'</p></div>';

    update_option('tpnnnf-result', array());
}

function tpnnnf_add_custom_box(){
    global $wp_version;
    if( function_exists( 'add_meta_box' )) {
        if(version_compare($wp_version, "2.7", "<")){
            add_meta_box('twitplusnnnf_post', __('TwitPlusNNNF', 'tpnnnf_textdomain' ) , 'twitplusnnnf_post_content', 'post', 'normal', 'high');
        } else {
            add_meta_box('twitplusnnnf_post', __('TwitPlusNNNF', 'tpnnnf_textdomain' ) , 'twitplusnnnf_post_content', 'post', 'side', 'high');
        }
    } else {
        add_action('dbx_post_advanced', 'twitplusnnnf_post_content_old' );
    }
}
function twitplusnnnf_post_content(){
    global $post;
    $storedPass = TWITPLUSNNNF_STORED_PASS;
    $stored_text = get_option('tpnnnf-stored-text');
    if (empty($stored_text))
        $stored_text = 'My new blog post "{title}" {nnnf}';
    $stored_user = get_option('tpnnnf-stored-user');
    $stored_pass = empty($stored_user) ? '' : $storedPass;

    $fc = get_option('tpnnnf-force-checked');
    if ($fc == 1){
        $cb = 'checked';
        update_option('tpnnnf-force-checked', 0);
    }
    else{
        $cb = get_option('tpnnnf-checked') == 1 ? 'checked' : '';

        if ($post->post_status == 'publish')
            $cb = '';
    }
?>
    <input type="hidden" name="tpnnnf_noncename" id="tpnnnf_noncename" value="<?php echo wp_create_nonce( plugin_basename(__FILE__) ) ?>" />
    <label for="tpnnnf_text"><?php echo __("Twitter post text:", 'tpnnnf_textdomain' ) ?></label>
    <textarea name="tpnnnf_text" id="tpnnnf_text" style="width:95%;height:5em;" wrap="hard"><?php echo $stored_text ?></textarea><br/>

    <input type="checkbox" name="tpnnnf_post" id="tpnnnf_post" value="1" <?php echo $cb?>  />
    <label for="tpnnnf_post"><?php echo __("When I publish post to twitter", 'tpnnnf_textdomain' ) ?></label><br />
    <a href="javascript:;" class="hide-if-no-js"
        onclick="jQuery('.tpnnnf_options').slideDown('normal');
            jQuery('#tpnnnf-edit').hide();
            jQuery('#tpnnnf-for-password').append('<input type=\'password\' id=\'tpnnnf-tmp-password\' name=\'tpnnnf-tmp-password\'>');
            jQuery('#tpnnnf-tmp-password').val(jQuery('#tpnnnf_pass').val());" id="tpnnnf-edit">Edit twitter settings</a>
     | <a href="javascript:;" class="hide-if-no-js" onclick="jQuery('.tpnnnf_help').slideDown('normal');jQuery('#tpnnnf-help').hide();" id="tpnnnf-help">Show help</a>
    <div id="tpnnnf_options" class="tpnnnf_options hide-if-js" style="padding:3px;">
    <input type="text" name="tpnnnf_user" id="tpnnnf_user" value="<?php echo $stored_user ?>"/> Username<br />
    <span id="tpnnnf-for-password"></span>
    <input type="hidden" name="tpnnnf_pass" id="tpnnnf_pass" value="<?php echo $stored_pass ?>"/> Password
    <br />
    <p>&nbsp; &nbsp;
    <a class="button" href="javascript:;"
        onclick="jQuery('#tpnnnf-status').html('Testing...');
            jQuery.post('admin-ajax.php', {'action':'tpnnnf_testacc', 'cookie':encodeURIComponent(document.cookie), 'username':jQuery('#tpnnnf_user').val(), 'password':jQuery('#tpnnnf-tmp-password').val()},
                function (res){if (res == 10) {jQuery('#tpnnnf-status').html('Invalid')}; if (res == 20) {jQuery('#tpnnnf-status').html('Confirmed')};
                if (res == 30) {jQuery('#tpnnnf-status').html('Twitter is too busy.')}; if (res == 0) {jQuery('#tpnnnf-status').html('Try again.')}; });
                ">Test</a>
    <a href="javascript:;"
        onclick="jQuery('.tpnnnf_options').slideUp('normal');
            jQuery('#tpnnnf-edit').show();
            jQuery('#tpnnnf_pass').val(jQuery('#tpnnnf-tmp-password').val());
            jQuery('#tpnnnf-for-password').empty();" class="button">Hide</a>
     &nbsp; <span id="tpnnnf-status"></span></p>
    </div>
    <div id="tpnnnf_help" class="tpnnnf_help hide-if-js" style="padding:3px;">
    <br \>
    {nnnf} - short url to your post (http...) <br \>
    {wnnnf} - short url to your post (www...)<br \>
    {url} - original url to your post<br \>
    {title} - post title<br \>
    {blog-name} - blog name<br \>
    {blog-url} - blog url<br \>

    <br /><a href="javascript:;" onclick="jQuery('.tpnnnf_help').slideUp('normal');jQuery('#tpnnnf-help').show();">Hide help</a>
    </div>
<?php
}
function twitplusnnnf_post_content_old(){
    echo 'Sorry. Your WP verison is not supported. WP 2.5+ required.';
}
function tpnnnf_test_acc(){
    $storedPass = TWITPLUSNNNF_STORED_PASS;
    $twURL  = TWITPLUSNNNF_TWITTER_URL;

    $user = $_POST['username'];
    update_option('tpnnnf-stored-user', $user);
    $pass = $_POST['password'];
    if ($pass == $storedPass)
        $pass = get_option('tpnnnf-stored-pass');
    else
        update_option('tpnnnf-stored-pass', $pass);
    $res = tpnnnf_load_url($twURL.'/account/verify_credentials.json', '', $user, $pass);

    if (is_numeric(strpos($res, 'followers_count'))){
        echo 2;
        return;
    }
    if (is_numeric(strpos($res, 'error'))){
        echo 1;
        return;
    } else {
        echo 3;
        return;
    }
}

function tpnnnf_load_url($url, $ps, $user='', $pass=''){

    $headers = array('Expect:');

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_USERAGENT, 'TwitPlusNNNF '.TWITPLUSNNNF_VERSION);
    curl_setopt($ch, CURLOPT_URL, $url);

    if (!empty($ps)){
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $ps);
    }
    if (!empty($user))
        curl_setopt($ch, CURLOPT_USERPWD, $user.':'.$pass);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 20);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);

    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}
function tpnnnf_save_postdata($postID, $pData){
    global $post, $tpnnnf;
    $twURL  = TWITPLUSNNNF_TWITTER_URL;
    $storedPass = TWITPLUSNNNF_STORED_PASS;
    if ( !wp_verify_nonce( $_POST['tpnnnf_noncename'], plugin_basename(__FILE__) ))
        return $postID;

    if ( 'post' == $_POST['post_type'] ) {
        if ( !current_user_can( 'edit_post', $postID ))
            return $postID;

        $twt_text = stripslashes($_POST['tpnnnf_text']);
        update_option('tpnnnf-stored-text', $twt_text);

        $user = $_POST['tpnnnf_user'];
        $pass = (isset($_POST['tpnnnf-tmp-password'])) ? $_POST['tpnnnf-tmp-password'] : $_POST['tpnnnf_pass'];
        update_option('tpnnnf-stored-user', $user);
        if ($pass != $storedPass)
            update_option('tpnnnf-stored-pass', $pass);


        if (isset($_POST['tpnnnf_post'])){
            if ($pass == $storedPass)
                 $pass = get_option('tpnnnf-stored-pass');

            update_option('tpnnnf-checked', 1);
            // post_title, post_content
            $permalink = get_permalink($postID);
            $twt_text = str_replace('{url}', $permalink, $twt_text);
            $twt_text = str_replace('{title}', $pData->post_title, $twt_text);
            $twt_text = str_replace('{blog-name}', get_bloginfo('name'), $twt_text);
            $twt_text = str_replace('{blog-url}', get_bloginfo('siteurl'), $twt_text);
            if (is_numeric(strpos($twt_text, '{nnnf}')) || is_numeric(strpos($twt_text, '{wnnnf}'))){
                $nnnf = tpnnnf_load_url('http://nn.nf/api.php', array('url'=>$permalink));

                if (substr($nnnf, 0, 13) == 'http://nn.nf/'){
                    $twt_text = str_replace('{nnnf}', $nnnf, $twt_text);
                    $wnnnf = str_replace('http://', 'www.', $nnnf);
                    $twt_text = str_replace('{wnnnf}', $wnnnf, $twt_text);
                }
            }

            $status = substr($twt_text, 0, 140);
            $res = tpnnnf_load_url($twURL.'/statuses/update.xml', array('status'=>$status), $user, $pass);

            if (is_numeric(strpos($res, 'Could not authenticate you'))){
                update_option('tpnnnf-result', array('error'=>true, 'message'=>"Invalid twitter username or password. Please check settings."));
                update_option('tpnnnf-force-checked', 1);
            } elseif(is_numeric(strpos($res, '<in_reply_to_screen_name>'))) {
                $status = str_replace($nnnf, "<a href='$nnnf' target='_blank'>$nnnf</a>", $status);
                $status = str_replace($wnnnf, "<a href='http://$wnnnf' target='_blank'>$wnnnf</a>", $status);
                update_option('tpnnnf-result', array('error'=>false, 'message'=>"<br/>Twitter status updated: $status"));
            } else {
                update_option('tpnnnf-result', array('error'=>true, 'message'=>"Twitter server is too busy. Please try again."));
                update_option('tpnnnf-force-checked', 1);
            }

        } else {
            update_option('tpnnnf-checked', 0);
        }
    }
}
?>
