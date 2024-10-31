<?php

/**
 * PHP version 7.2
 *
 * Plugin Name: Multi-level menu for Ecwid
 * Plugin URI: https://bit.ly/2uJbiCg
 *
 * Text Domain: kinvasoft-multi-level-menu
 *
 * Description: Multi-level menu for Ecwid.
 *
 * Author: Kinvasoft <info@kinvasoft.com>
 * Author URI: https://kinvasoft.com
 * Version: 1.0.1
 *
 * Copyright (c) 2013-present <info@kinvasoft.com>. All rights reserved
 */

require_once(ABSPATH . 'wp-admin/includes/plugin.php');

class Kinvasoft_MultiLevelMenu extends WP_Widget
{
    const SERVICE_CODE = 'kinvasoft-multi-level-menu-for-ecwid';
    const SERVICE_NAME = 'Multi-level menu for Ecwid';

    const PUBLIC_TOKEN = 'kinvasoft-multi-level-menu-token';

    const APP_NAME    = 'multi-level-menu-dev';
    const APP_VERSION = 'src';

    public function __construct()
    {
        parent::__construct(
            Kinvasoft_MultiLevelMenu::SERVICE_CODE,
            __(Kinvasoft_MultiLevelMenu::SERVICE_NAME, Kinvasoft_MultiLevelMenu::APP_NAME),
            array(
                'customize_selective_refresh' => true,
            )
        );
    }

    public function form($instance)
    {
        $defaults = array(
            'select'   => '',
        );

        extract(wp_parse_args((array) $instance, $defaults)); ?>

        <p>
            <label for="<?php echo $this->get_field_id('select'); ?>"><?php _e('Menu layout:', Kinvasoft_MultiLevelMenu::getTranslationDomain()); ?></label>
            <select name="<?php echo $this->get_field_name('select'); ?>" id="<?php echo $this->get_field_id('select'); ?>" class="widefat">
<?php

                $options = array(
                    '0' => __('Horizontal', Kinvasoft_MultiLevelMenu::getTranslationDomain()),
                    '1' => __('Vertical', Kinvasoft_MultiLevelMenu::getTranslationDomain()),
                );

                foreach ($options as $key => $name) {
                    echo '<option value="' . esc_attr($key) . '" id="' . esc_attr($key) . '" ' . selected($select, $key, false) . '>' . $name . '</option>';
                }
?>
            </select>
        </p>
<?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance = $old_instance;

        $instance['select'] = isset($new_instance['select']) ? wp_strip_all_tags($new_instance['select']) : '';

        return $instance;
    }

    public function widget($args, $instance)
    {
        extract($args);

        $select = isset($instance['select']) ? $instance['select'] : '';

        echo $before_widget;
        echo '<div class="widget-text wp-widget-plugin-box">';

        $config = [];
        if ($select == 1) {
            $config += [
                'tpLayoutStyle' => 'vertical'
            ];
        }

        echo self::getWidgetCode($config);

        echo '</div>';
        echo $after_widget;
    }

    public static function getMenuObject()
    {
        $multi_level_menu_id = get_option(Kinvasoft_MultiLevelMenu::SERVICE_CODE);
        $multi_level_menu_object = wp_get_nav_menu_object($multi_level_menu_id);

        return $multi_level_menu_object;
    }

    public static function getWidgetCode($config = [])
    {
        $appName = Kinvasoft_MultiLevelMenu::APP_NAME;
        $appVer = Kinvasoft_MultiLevelMenu::APP_VERSION;

        if (self::isEcwidPluginActive()) {
            $id = get_ecwid_store_id();
            $token = get_option(Kinvasoft_MultiLevelMenu::PUBLIC_TOKEN);

            $config += [
                'appWidgetSource'   => 'WordPress',
                'appWidgetStoreUrl' => Ecwid_Store_Page::get_store_url(),
            ];
        }

        $configAttribute = '';

        if (!empty($config)) {
            $configAttribute = 'data-config="' . base64_encode(json_encode((object) $config)) . '" ';
        }

        $widgetCode = '<script src="https://ecwid.kinvasoft.com/apps/launcher/build/1.1/widgets.min.js?id=' . $appName . '&amp;version=' . $appVer . '&amp;owner=' . $id . '&amp;token=' . $token . '" ' . $configAttribute . 'async=""></script>';

        return $widgetCode;
    }

    public static function isEcwidPluginActive()
    {
        return is_plugin_active('ecwid-shopping-cart/ecwid-shopping-cart.php');
    }

    public static function isMenuAppInstalled()
    {
        if (!Kinvasoft_MultiLevelMenu::isEcwidPluginActive()) {
            return false;
        }

        $items = EcwidPlatform::get('admin_menu');

        $result = false;

        array_walk_recursive($items, function ($item, $key) use (&$result) {
            if (
                $key === 'path'
                && $item === ('app:name=' . self::APP_NAME)
            ) {
                $result = true;
            }
        });

        return $result;
    }

    public static function getTranslationDomain()
    {
        return str_replace('-dev', '', self::APP_NAME);
    }
}

function kinvasoft_multi_level_menu_widget_init()
{
    register_widget('Kinvasoft_MultiLevelMenu');
}

function kinvasoft_multi_level_menu_plugin_row_meta($plugin_meta, $pluginFile)
{
    if (plugin_basename(__FILE__) === $pluginFile) {

        foreach ($plugin_meta as $existing_link) {
            if (strpos($existing_link, 'tab=plugin-information') !== false) {
                return $plugin_meta;
            }
        }

        $plugin_info = get_plugin_data(__FILE__);

        $plugin_meta[] = sprintf(
            '<a href="%s" class="thickbox open-plugin-details-modal" aria-label="%s" data-title="%s">%s</a>',
            esc_url(network_admin_url('plugin-install.php?tab=plugin-information&plugin=' . Kinvasoft_MultiLevelMenu::SERVICE_CODE . '&TB_iframe=true&width=600&height=550')),
            esc_attr(sprintf(__('More information about %s', Kinvasoft_MultiLevelMenu::getTranslationDomain()), $plugin_info['Name'])),
            esc_attr($plugin_info['Name']),
            __('View details', Kinvasoft_MultiLevelMenu::getTranslationDomain())
        );
    }

    return $plugin_meta;
}
add_filter('plugin_row_meta', 'kinvasoft_multi_level_menu_plugin_row_meta', 10, 2);

function kinvasoft_multi_level_menu_add_icon()
{
?>
    <style>
        *[id*="_kinvasoft-multi-level-menu"]>div.widget-top>div.widget-title>h3:before {
            content: url('<?php echo plugins_url('icon.png', __FILE__ );?>');
            width: 33px;
            float: left;
            height: 8px;
            margin-top: -7px;
            margin-top: -4px;
        }
    </style>
<?php
}
add_action('admin_head-widgets.php', 'kinvasoft_multi_level_menu_add_icon');

function kinvasoft_multi_level_menu_activate()
{
    $multi_level_menu_object = Kinvasoft_MultiLevelMenu::getMenuObject();

    if (!$multi_level_menu_object) {

        $multi_level_menu_id = wp_update_nav_menu_object(0, [
            'menu-name' => Kinvasoft_MultiLevelMenu::SERVICE_NAME
        ]);

        if (!is_wp_error($multi_level_menu_id)) {

            add_option(Kinvasoft_MultiLevelMenu::SERVICE_CODE, $multi_level_menu_id);
        }
    }
}
register_activation_hook(__FILE__, 'kinvasoft_multi_level_menu_activate');

function kinvasoft_multi_level_menu_deactivate()
{
    wp_delete_nav_menu(Kinvasoft_MultiLevelMenu::getMenuObject());

    delete_option(Kinvasoft_MultiLevelMenu::SERVICE_CODE);
}
register_deactivation_hook(__FILE__, 'kinvasoft_multi_level_menu_deactivate');

function kinvasoft_multi_level_menu_uninstall()
{
    wp_delete_nav_menu(Kinvasoft_MultiLevelMenu::getMenuObject());

    delete_option(Kinvasoft_MultiLevelMenu::SERVICE_CODE);
}
register_uninstall_hook(__FILE__, 'kinvasoft_multi_level_menu_uninstall');

function kinvasoft_multi_level_menu_delete_nav_menu($term_id)
{
    if ($term_id == get_option(Kinvasoft_MultiLevelMenu::SERVICE_CODE)) {
        delete_option(Kinvasoft_MultiLevelMenu::SERVICE_CODE);
    }
}
add_action('wp_delete_nav_menu', 'kinvasoft_multi_level_menu_delete_nav_menu');

function kinvasoft_multi_level_menu_settings_page()
{

    if ($_GET['edit-token'] == 'on') {

        if (Kinvasoft_MultiLevelMenu::isEcwidPluginActive()) {

            if (Kinvasoft_MultiLevelMenu::isMenuAppInstalled()) {

?>

        <div class="wrap multi-level-menu-settings">

            <h2><?php _e('Multi-level menu for Ecwid', Kinvasoft_MultiLevelMenu::getTranslationDomain()); ?></h2>

<?php

            if (isset($_POST['save'])) {

                $token = sanitize_text_field($_POST['token']);

                update_option(Kinvasoft_MultiLevelMenu::PUBLIC_TOKEN, '' . $token);

?>

                <div class="notice notice-success is-dismissible">
                    <p><?php _e('Settings saved successfully!', Kinvasoft_MultiLevelMenu::getTranslationDomain()); ?></p>
                </div>

<?php

            }

?>

            <p>
                <?php _e('To configure the Multi-level menu, you need to open the menu settings page <b>Menu widget > Widget HTML code</b>. Then click on the <b>Copy token</b> button. See the example in the screenshot below.', Kinvasoft_MultiLevelMenu::getTranslationDomain()); ?>
            </p>
            <figure>
                <img src="<?php echo plugins_url('sample-token.png', __FILE__ );?>" />
            </figure>
            <p>
                <?php _e('The copied code must be added to the "<b>Token</b>" field below. Finally click on the "<b>Save</b>" button to save changes.', Kinvasoft_MultiLevelMenu::getTranslationDomain()); ?>
            </p>

            <div class="welcome-panel">
                <form method="post">
                    <div class="input-text-wrap">
                        <label for="token"><?php _e('Token', Kinvasoft_MultiLevelMenu::getTranslationDomain()); ?>:</label>
                        <br />
                        <input type="text" name="token" class="public-token" autocomplete="off" value="<?php echo get_option(Kinvasoft_MultiLevelMenu::PUBLIC_TOKEN); ?>" />
                    </div>
                    <p class="submit">
                        <input type="submit" name="save" class="button button-primary" value="<?php _e('Save', Kinvasoft_MultiLevelMenu::getTranslationDomain()); ?>" />
                    </p>
                </form>
            </div>
        </div>

<?php

            }

        } else {

?>

        <div class="wrap">

            <h2><?php _e('Multi-level menu for Ecwid', Kinvasoft_MultiLevelMenu::getTranslationDomain()); ?></h2>

            <div class="welcome-panel">

                <?php _e('The Multi-level menu for Ecwid plugin works in conjunction with Ecwid. For its work you need:<br/>1. Register an account with Ecwid (<a href="http://open.ecwid.com/hfZSF">ecwid.com</a>).<br/>2. Install the Ecwid plugin in WordPress (<a href="plugin-install.php?tab=plugin-information&plugin=ecwid-shopping-cart">link to the plugin</a>).<br/>3. Install the Multi-level menu application in Ecwid (<a href="https://bit.ly/2uJbiCg">link to the app</a>).<br/>4. Set up the Multi-level menu for Ecwid plugin in WordPress.', Kinvasoft_MultiLevelMenu::getTranslationDomain()); ?>

                <p class="submit">

                    <a href="http://open.ecwid.com/hfZSF" class="button button-primary">
                        <?php _e('Proceed with registration at Ecwid', Kinvasoft_MultiLevelMenu::getTranslationDomain()); ?>
                    </a>

                </p>

            </div>

        </div>

<?php

        }

    } else {

        if (
            Kinvasoft_MultiLevelMenu::isEcwidPluginActive()
            && Kinvasoft_MultiLevelMenu::isMenuAppInstalled()
        ) {
?>

            <div class="wrap multi-level-menu-redirect">

                <h2><?php _e('Multi-level menu for Ecwid', Kinvasoft_MultiLevelMenu::getTranslationDomain()); ?></h2>

                <div class="welcome-panel">
                    <?php _e('Redirecting to app configuration page...', Kinvasoft_MultiLevelMenu::getTranslationDomain()); ?>
                </div>

            </div>

            <script>
                window.location.replace('admin.php?page=ec-store-admin-app-name-<?php echo Kinvasoft_MultiLevelMenu::APP_NAME; ?>');
            </script>

<?php
        } else {

            if (!Kinvasoft_MultiLevelMenu::isEcwidPluginActive()) {

?>

                <div class="wrap">

                    <h2><?php _e('Multi-level menu for Ecwid', Kinvasoft_MultiLevelMenu::getTranslationDomain()); ?></h2>

                    <div class="welcome-panel">

                        <?php _e('The Multi-level menu for Ecwid plugin works in conjunction with Ecwid. For its work you need:<br/>1. Register an account with Ecwid (<a href="http://open.ecwid.com/hfZSF">ecwid.com</a>).<br/>2. Install the Ecwid plugin in WordPress (<a href="plugin-install.php?tab=plugin-information&plugin=ecwid-shopping-cart">link to the plugin</a>).<br/>3. Install the Multi-level menu application in Ecwid (<a href="https://bit.ly/2uJbiCg">link to the app</a>).<br/>4. Set up the Multi-level menu for Ecwid plugin in WordPress.', Kinvasoft_MultiLevelMenu::getTranslationDomain()); ?>

                        <p class="submit">

                            <a href="http://open.ecwid.com/hfZSF" class="button button-primary">
                                <?php _e('Proceed with registration at Ecwid', Kinvasoft_MultiLevelMenu::getTranslationDomain()); ?>
                            </a>

                        </p>

                    </div>

                </div>

<?php
            } else {

?>

                <div class="wrap">

                    <h2><?php _e('Multi-level menu for Ecwid', Kinvasoft_MultiLevelMenu::getTranslationDomain()); ?></h2>

                    <div class="welcome-panel">

                        <?php _e('The Multi-level menu for Ecwid plugin works in conjunction with Ecwid. For its work you need:<br/><strike>1. Register an account with Ecwid (<a href="http://open.ecwid.com/hfZSF">ecwid.com</a>).</strike><br/><strike>2. Install the Ecwid plugin in WordPress (<a href="plugin-install.php?tab=plugin-information&plugin=ecwid-shopping-cart">link to the plugin</a>).</strike><br/>3. Install the Multi-level menu application in Ecwid (<a href="https://bit.ly/2uJbiCg">link to the app</a>).<br/>4. Set up the Multi-level menu for Ecwid plugin in WordPress.', Kinvasoft_MultiLevelMenu::getTranslationDomain()); ?>

                        <p class="submit">

                            <a href="http://open.ecwid.com/hfZSF" class="button button-primary">
                                <?php _e('Proceed with registration at Ecwid', Kinvasoft_MultiLevelMenu::getTranslationDomain()); ?>
                            </a>

                        </p>

                    </div>

                </div>

<?php

            }

        }

    }
}

function kinvasoft_multi_level_menu_admin_menu()
{
    if (current_user_can('edit_plugins')) {

        add_menu_page('Multi-level menu for Ecwid', __('Multi-level menu', Kinvasoft_MultiLevelMenu::getTranslationDomain()), 'manage_options', 'kinvasoft_multi_level_menu_settings_page', 'kinvasoft_multi_level_menu_settings_page', 'dashicons-align-right', 4);
    }
}
add_action('admin_menu', 'kinvasoft_multi_level_menu_admin_menu');

function kinvasoft_multi_level_menu_settings($links)
{
    $links = array_merge([
        'settings' => '<a href="' . admin_url('admin.php?page=kinvasoft_multi_level_menu_settings_page&edit-token=on') . '">' . __('Settings', Kinvasoft_MultiLevelMenu::getTranslationDomain()) . '</a>'
    ], $links);

    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'kinvasoft_multi_level_menu_settings');

function kinvasoft_multi_level_menu_inline_js_admin() {
    $multi_level_menu_object = Kinvasoft_MultiLevelMenu::getMenuObject();
    $multi_level_menu_object_id = $multi_level_menu_object->term_id;

    global $nav_menu_selected_id;

    if ($nav_menu_selected_id === $multi_level_menu_object_id) {
        $js = '<script>
            document.addEventListener("DOMContentLoaded", function(event) {
                document.getElementById("menu-instructions").innerHTML = "<p>' . __('You can Add / Edit menu items on the application options', Kinvasoft_MultiLevelMenu::getTranslationDomain()) . ' <a href=admin.php?page=ec-store-admin-app-name-multi-level-menu>' . __('configuration page', Kinvasoft_MultiLevelMenu::getTranslationDomain()) . '</a>.</p>";
                document.getElementById("menu-instructions").style.display = "block";
            });
        </script>';

        echo $js;
    }
}
add_action('admin_footer', 'kinvasoft_multi_level_menu_inline_js_admin');

function kinvasoft_multi_level_menu_inline_css_admin()
{
    $multi_level_menu_object = Kinvasoft_MultiLevelMenu::getMenuObject();
    $multi_level_menu_object_id = $multi_level_menu_object->term_id;

    global $nav_menu_selected_id;

    if ($nav_menu_selected_id === $multi_level_menu_object_id) {
        $css = '
            <style>
                #nav-menus-frame {
                    margin-left: 0px;
                }
                #nav-menus-frame #menu-settings-column,
                #nav-menus-frame #menu-to-edit,
                #nav-menus-frame .drag-instructions,
                #nav-menus-frame .auto-add-pages,
                #nav-menus-frame .delete-action {
                    display: none!important;
                }
            </style>
        ';

        echo $css;
    }

    $css = '
        <style>
            @import url("https://fonts.googleapis.com/css?family=Roboto&display=swap");
            .multi-level-menu-settings p {
                font-family: "Roboto", sans-serif;
                font-size: 14px;
                line-height: 150%;
                text-align: justify;
            }
            .multi-level-menu-settings figure {
                margin: auto;
            }
            .multi-level-menu-settings img {
                width: 100%;
                max-width: 685px;
            }
            .multi-level-menu-settings .public-token {
                width: 100%;
                max-width: 420px;
            }
            .multi-level-menu-settings .button-primary {
                min-width: 100px;
            }
            .multi-level-menu-redirect .welcome-panel {
                padding-bottom: 25px;
            }
        </style>
    ';

    echo $css;
}
add_action('admin_head', 'kinvasoft_multi_level_menu_inline_css_admin');

function kinvasoft_multi_level_menu_nav_menu_items($items, $args)
{
    $multi_level_menu_object = Kinvasoft_MultiLevelMenu::getMenuObject();
    $multi_level_menu_object_id = $multi_level_menu_object->term_id;

    $locations = get_nav_menu_locations();
    foreach ($locations as $key => $location_object_id) {
        if ($multi_level_menu_object_id == $location_object_id) {
            if ($args->theme_location == $key) {
                $items = Kinvasoft_MultiLevelMenu::getWidgetCode();
            }
        }
    }

    return $items;
}
add_filter('wp_nav_menu_items', 'kinvasoft_multi_level_menu_nav_menu_items', 10, 2);

function kinvasoft_multi_level_menu_translation()
{
    load_plugin_textdomain(Kinvasoft_MultiLevelMenu::getTranslationDomain(), false, dirname(plugin_basename(__FILE__)) . '/lang');
}
add_action('plugins_loaded', 'kinvasoft_multi_level_menu_translation');

if (Kinvasoft_MultiLevelMenu::isEcwidPluginActive()) {
    add_action('widgets_init', 'kinvasoft_multi_level_menu_widget_init');
}
