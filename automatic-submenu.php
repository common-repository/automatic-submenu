<?php
/**
 * @package AutomaticSubmenu
 */
/*
Plugin Name: Automatic Submenu
Plugin URI: https://github.com/wp-automatic-plugin
Description: Automatically append children posts and pages as submenu items
Version: 1.0.0
Author: John Rallis (rallisf1)
Author URI: https://www.facebook.com/rallisf1
License: GPL2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
Text Domain: automatic-submenu

Automatic Submenu is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
Automatic Submenu is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with Automatic Submenu. If not, see https://www.gnu.org/licenses/old-licenses/gpl-2.0.html.
*/

if ( !defined( 'ABSPATH' ) )
{
	die;
}

if( !class_exists( 'AutomaticSubmenu' ) )
{
	class AutomaticSubmenu
	{
		/**
		 * Register actions & filters
		 *
		 */
		public function register()
		{
			// admin stuff
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
			add_action( 'save_post_nav_menu_item', array( $this, 'automaticsubmenu_save_post_action' ), 10, 3 );
			add_filter( 'wp_setup_nav_menu_item', array( $this, 'automaticsubmenu_wp_setup_nav_menu_item' ) );
			add_action( 'admin_init', array( $this, 'admin_init' ), 99 );
			// frontend menu
			add_filter('wp_get_nav_menu_items', array($this, 'menu_magic'));
		}
		
		/**
		 * Delayed admin actions
		 *
		 */
		
		public function admin_init()
		{
		// Add custom field for menu edit walker
			if ( !has_action( 'wp_nav_menu_item_custom_fields' ) ) {
				add_filter( 'wp_edit_nav_menu_walker', array( $this, 'callWalker' ) );
			}
			add_action( 'wp_nav_menu_item_custom_fields', array( $this, 'menu_item_custom_fields' ), 10, 4 );
		}
		
		/**
		 * Activation function
		 * Not sure if needed but added for precaution
		 */
		public function activate()
		{
			flush_rewrite_rules();
		}
		
		/**
		 * Deactivation function
		 * Not sure if needed but added for precaution
		 */
		public function deactivate()
		{
			flush_rewrite_rules();
		}
		
		/**
		 * Saving post action.
		 *
		 * Saving custom post meta to menu item post type.
		 *
		 * @param int     $post_id
		 * @param WP_Post $post
		 */
		public function automaticsubmenu_save_post_action( $post_id, $post )
		{
			$settings = array(
				'menu-item-automatic-max',
				'menu-item-automatic-order'
			);
			
			// checkbox needs special handling
			if ( isset( $_POST[ 'menu-item-automatic' ][ $post_id ] ) ){
				update_post_meta( $post_id, '_menu_item_automatic', "1" );
			} else {
				update_post_meta( $post_id, '_menu_item_automatic', "0" );
			}
			
			// the rest can go in a loop
			foreach ( $settings as $setting_name ) {
				$db_name = str_replace('-', '_', $setting_name);
				if ( isset( $_POST[ $setting_name ][ $post_id ] ) && (!empty( $_POST[ $setting_name ][ $post_id ] ) || ( $_POST[ $setting_name ][ $post_id ] == "0" ) ) ) {
					if ( $post->{"_$db_name"} != $_POST[ $setting_name ][ $post_id ] ) {
						update_post_meta( $post_id, "_$db_name", esc_sql( $_POST[ $setting_name ][ $post_id ] ) );
					}
				}
			}
		}
		
		/**
		 * Load menu item meta for each menu item.
		 */
		public function automaticsubmenu_wp_setup_nav_menu_item( $item )
		{
			if ( !isset( $item->automatic ) ) {
				$item->automatic = get_post_meta( $item->ID, '_menu_item_automatic', true );
			}
			if ( !isset( $item->automatic_max ) ) {
				$item->automatic_max = get_post_meta( $item->ID, '_menu_item_automatic_max', true );
			}
			if ( !isset( $item->automatic_order ) ) {
				$item->automatic_order = get_post_meta( $item->ID, '_menu_item_automatic_order', true );
			}

			return $item;
		}
		
		/**
		 * Enqueue the admin script
		 */
		public function enqueue()
		{
			wp_enqueue_script( 'automaticsubmenujs', plugins_url( '/assets/script.js', __FILE__ ) );
		}
		
		/**
		 * Add custom fields to menu item.
		 *
		 * @param int    $item_id
		 * @param object $item
		 * @param int    $depth
		 * @param array  $args
		 *
		 */
		public function menu_item_custom_fields( $item_id, $item, $depth, $args )
		{
			if ( !$item_id && isset( $item->ID ) ) {
				$item_id = $item->ID;
			}
			// We only need them for post categories and pages
			if ( $item->post_status == 'publish' && ( $item->object == 'category' || $item->object == 'page' ) ) {
				echo '
				<p class="description">
					<label for="edit-menu-item-automatic-' . $item_id . '">
						<input type="checkbox" id="edit-menu-item-automatic-' . $item_id . '" value="automatic" name="menu-item-automatic[' . $item_id . ']"' . (($item->automatic) ? "checked" : "") . '>
						Automatically display children as submenu items</label>
				</p>
				<p class="field-automatic-max description description-thin hidden-field">
					<label for="edit-menu-item-automatic-max' . $item_id . '">
						Maximum child items<br />
						<input type="number" class="widefat" id="edit-menu-item-automatic-max-' . $item_id . '" value="' . $item->automatic_max . '" name="menu-item-automatic-max[' . $item_id . ']" min="1" max="99">
					</label>
				</p>
				<p class="field-automatic-order description description-thin hidden-field">
					<label for="edit-menu-item-automatic-order' . $item_id . '">
						Children ordering<br />
						<select id="edit-menu-item-automatic-order-' . $item_id . '" name="menu-item-automatic-order[' . $item_id . ']" class="widefat">
							<option value="title_asc"' . ( ( $item->automatic_order == "title_asc" ) ? 'selected="selected"' : "" ) . '>Title ↑</option>
							<option value="title_desc"' . ( ( $item->automatic_order == "title_desc" ) ? 'selected="selected"' : "" ) . '>Title ↓</option>
							<option value="date_asc"' . ( ( $item->automatic_order == "date_asc" ) ? 'selected="selected"' : "" ) . '>Publish date ↑</option>
							<option value="date_desc"' . ( ( $item->automatic_order == "date_desc" ) ? 'selected="selected"' : "" ) . '>Publish date ↓</option>
						</select>
					</label>
				</p>
				';
			}
		}
		
		/**
		 * Replacement edit menu walker class.
		 *
		 * @return string
		 */
		public function callWalker()
		{
			return 'AutomaticSubmenu_Walker_Nav_Menu_Edit';
		}
		
		/**
		 * Retrieve navmenu items
		 *
		 * @param array $items List of nav-menu items objects.
		 * @return array
		 */
		public function menu_magic( $items )
		{
			// we don't want to add them in the backend, else if they are saved the db will get really ugly
			if( is_admin() )
				return $items;
			
			$additions = array();
			// we need a global var to calc the menu_order of the additions
			global $automaticsubmenu_children_order;
			$automaticsubmenu_children_order = count($items) + 1;
			
			foreach ( (array) $items as $item ) {
				if( $item->automatic == 1 ) {
					if( empty( $item->automatic_max ) ) {
						// if max children field left empty default to 5, change it if you wish
						$item->automatic_max = 5;
					}
					$ordering = explode('_', $item->automatic_order);
					if( $item->object == "page" ) {
						// look for child pages
						// 'child_of' returns all ascendants no matter how deep, change to 'parent' for direct children
						$children = get_pages( array( 'child_of' => $item->object_id, 'number' => $item->automatic_max, 'sort_column' => 'post_'.$ordering[0], 'sort_order' => strtoupper( $ordering[1] ) ) );
						if( $children ) {
							$additions[] = $this->createSubmenu($children, $item, 'pages');
						}
					} elseif ( $item->object == "category" ) {
						// look for child posts
						$children = get_posts( array( 'numberposts' => $item->automatic_max, 'category' => $item->object_id, 'orderby' => $ordering[0], 'order' => strtoupper( $ordering[1] ) ) );
						if($children){
							$additions[] = $this->createSubmenu( $children, $item );
						}
					}
				}
			}
			
			return array_merge( (array) $items, $additions[0] );
		}
		
		/**
		 * Create submenu items from child posts/pages
		 *
		 * @param array $children List of child posts/pages
		 * @param object $parent The parent nav menu item
		 * @param string $type Defaults to 'posts' but can be 'pages' as well
		 * @return array
		 */
		private function createSubmenu( $children, $parent, $type = 'posts' )
		{
			global $automaticsubmenu_children_order;
			$new_items = array();
			foreach( $children as $child ){
				$tmpItem = new WP_Post();
				$tmpItem->ID = $child->ID * 10000;
				$tmpItem->post_author = $parent->post_author;
				$tmpItem->post_date = $child->post_date;
				$tmpItem->post_date_gmt = $child->post_date_gmt;
				$tmpItem->post_content = '';
				$tmpItem->post_title = '';
				$tmpItem->post_excerpt = '';
				$tmpItem->post_status = 'publish';
				$tmpItem->comment_status = $child->comment_status;
				$tmpItem->ping_status = $child->ping_status;
				$tmpItem->post_password = '';
				$tmpItem->post_name = $child->post_name;
				$tmpItem->to_ping = '';
				$tmpItem->pinged = '';
				$tmpItem->post_modified = $child->post_modified;
				$tmpItem->post_modified_gmt = $child->post_modified_gmt;
				$tmpItem->post_content_filtered = '';
				$tmpItem->post_parent = ($type == 'pages') ? $parent->object_id : 0;
				$tmpItem->guid = $child->guid;
				$tmpItem->menu_order = $automaticsubmenu_children_order;
				$tmpItem->post_type = 'nav_menu_item';
				$tmpItem->post_mime_type = '';
				$tmpItem->comment_count = $child->comment_count;
				$tmpItem->filter = 'raw';
				$tmpItem->db_id = $tmpItem->ID;
				$tmpItem->menu_item_parent = $parent->ID;
				$tmpItem->object_id = $child->ID;
				$tmpItem->object = substr($type, 0, -1);
				$tmpItem->type = 'post_type';
				$tmpItem->type_label = '';
				$tmpItem->url = ($type == 'pages') ? get_page_link($child->ID) : get_permalink($child->ID);
				$tmpItem->title = (!empty(trim($child->post_title))) ? $child->post_title : $child->title;
				$tmpItem->target = '';
				$tmpItem->attr_title = '';
				$tmpItem->description = '';
				$tmpItem->classes = array ( 0 => '' );
				$tmpItem->xfn = '';
				
				
				$new_items[] = $tmpItem;
				$automaticsubmenu_children_order++;
			}
			
			return $new_items;
		}
		
	}
	// initiate our plugin
	$automaticSubmenu = new AutomaticSubmenu();
	$automaticSubmenu->register();
}

// we need to override the backend menu walker for our custom fields to show up
// credits to zviryatko (https://plugins.trac.wordpress.org/browser/menu-image/trunk/menu-image.php)

require_once(ABSPATH . 'wp-admin/includes/nav-menu.php');

class AutomaticSubmenu_Walker_Nav_Menu_Edit extends Walker_Nav_Menu_Edit
{
	public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 )
	{
		global $_wp_nav_menu_max_depth;
		$_wp_nav_menu_max_depth = $depth > $_wp_nav_menu_max_depth ? $depth : $_wp_nav_menu_max_depth;

		ob_start();
		$item_id = esc_attr( $item->ID );
		$removed_args = array(
			'action',
			'customlink-tab',
			'edit-menu-item',
			'menu-item',
			'page-tab',
			'_wpnonce',
		);

		$original_title = '';
		if ( 'taxonomy' == $item->type ) {
			$original_title = get_term_field( 'name', $item->object_id, $item->object, 'raw' );
			if ( is_wp_error( $original_title ) )
				$original_title = false;
		} elseif ( 'post_type' == $item->type ) {
			$original_object = get_post( $item->object_id );
			$original_title = get_the_title( $original_object->ID );
		}

		$classes = array(
			'menu-item menu-item-depth-' . $depth,
			'menu-item-' . esc_attr( $item->object ),
			'menu-item-edit-' . ( ( isset( $_GET['edit-menu-item'] ) && $item_id == $_GET['edit-menu-item'] ) ? 'active' : 'inactive'),
		);

		$title = $item->title;

		if ( ! empty( $item->_invalid ) ) {
			$classes[] = 'menu-item-invalid';
			/* translators: %s: title of menu item which is invalid */
			$title = sprintf( __( '%s (Invalid)' ), $item->title );
		} elseif ( isset( $item->post_status ) && 'draft' == $item->post_status ) {
			$classes[] = 'pending';
			/* translators: %s: title of menu item in draft status */
			$title = sprintf( __('%s (Pending)'), $item->title );
		}

		$title = ( ! isset( $item->label ) || '' == $item->label ) ? $title : $item->label;

		$submenu_text = '';
		if ( 0 == $depth )
			$submenu_text = 'style="display: none;"';

		?>
		<li id="menu-item-<?php echo $item_id; ?>" class="<?php echo implode(' ', $classes ); ?>">
			<dl class="menu-item-bar">
				<dt class="menu-item-handle">
					<span class="item-title"><span class="menu-item-title"><?php echo esc_html( $title ); ?></span> <span class="is-submenu" <?php echo $submenu_text; ?>><?php _e( 'sub item' ); ?></span></span>
					<span class="item-controls">
						<span class="item-type"><?php echo esc_html( $item->type_label ); ?></span>
						<span class="item-order hide-if-js">
							<a href="<?php
								echo wp_nonce_url(
									add_query_arg(
										array(
											'action' => 'move-up-menu-item',
											'menu-item' => $item_id,
										),
										remove_query_arg($removed_args, admin_url( 'nav-menus.php' ) )
									),
									'move-menu_item'
								);
							?>" class="item-move-up"><abbr title="<?php esc_attr_e('Move up'); ?>">&#8593;</abbr></a>
							|
							<a href="<?php
								echo wp_nonce_url(
									add_query_arg(
										array(
											'action' => 'move-down-menu-item',
											'menu-item' => $item_id,
										),
										remove_query_arg($removed_args, admin_url( 'nav-menus.php' ) )
									),
									'move-menu_item'
								);
							?>" class="item-move-down"><abbr title="<?php esc_attr_e('Move down'); ?>">&#8595;</abbr></a>
						</span>
						<a class="item-edit" id="edit-<?php echo $item_id; ?>" title="<?php esc_attr_e('Edit Menu Item'); ?>" href="<?php
							echo ( isset( $_GET['edit-menu-item'] ) && $item_id == $_GET['edit-menu-item'] ) ? admin_url( 'nav-menus.php' ) : add_query_arg( 'edit-menu-item', $item_id, remove_query_arg( $removed_args, admin_url( 'nav-menus.php#menu-item-settings-' . $item_id ) ) );
						?>"><?php _e( 'Edit Menu Item' ); ?></a>
					</span>
				</dt>
			</dl>

			<div class="menu-item-settings wp-clearfix" id="menu-item-settings-<?php echo $item_id; ?>">
				<?php if( 'custom' == $item->type ) : ?>
					<p class="field-url description description-wide">
						<label for="edit-menu-item-url-<?php echo $item_id; ?>">
							<?php _e( 'URL' ); ?><br />
							<input type="text" id="edit-menu-item-url-<?php echo $item_id; ?>" class="widefat code edit-menu-item-url" name="menu-item-url[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $item->url ); ?>" />
						</label>
					</p>
				<?php endif; ?>
				<p class="description description-thin">
					<label for="edit-menu-item-title-<?php echo $item_id; ?>">
						<?php _e( 'Navigation Label' ); ?><br />
						<input type="text" id="edit-menu-item-title-<?php echo $item_id; ?>" class="widefat edit-menu-item-title" name="menu-item-title[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $item->title ); ?>" />
					</label>
				</p>
				<p class="description description-thin">
					<label for="edit-menu-item-attr-title-<?php echo $item_id; ?>">
						<?php _e( 'Title Attribute' ); ?><br />
						<input type="text" id="edit-menu-item-attr-title-<?php echo $item_id; ?>" class="widefat edit-menu-item-attr-title" name="menu-item-attr-title[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $item->post_excerpt ); ?>" />
					</label>
				</p>
				<p class="field-link-target description">
					<label for="edit-menu-item-target-<?php echo $item_id; ?>">
						<input type="checkbox" id="edit-menu-item-target-<?php echo $item_id; ?>" value="_blank" name="menu-item-target[<?php echo $item_id; ?>]"<?php checked( $item->target, '_blank' ); ?> />
						<?php _e( 'Open link in a new window/tab' ); ?>
					</label>
				</p>
				<p class="field-css-classes description description-thin">
					<label for="edit-menu-item-classes-<?php echo $item_id; ?>">
						<?php _e( 'CSS Classes (optional)' ); ?><br />
						<input type="text" id="edit-menu-item-classes-<?php echo $item_id; ?>" class="widefat code edit-menu-item-classes" name="menu-item-classes[<?php echo $item_id; ?>]" value="<?php echo esc_attr( implode(' ', $item->classes ) ); ?>" />
					</label>
				</p>
				<p class="field-xfn description description-thin">
					<label for="edit-menu-item-xfn-<?php echo $item_id; ?>">
						<?php _e( 'Link Relationship (XFN)' ); ?><br />
						<input type="text" id="edit-menu-item-xfn-<?php echo $item_id; ?>" class="widefat code edit-menu-item-xfn" name="menu-item-xfn[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $item->xfn ); ?>" />
					</label>
				</p>

				<?php
				// This is the added section
				do_action( 'wp_nav_menu_item_custom_fields', $item_id, $item, $depth, $args );
				// end added section
				?>

				<p class="field-description description description-wide">
					<label for="edit-menu-item-description-<?php echo $item_id; ?>">
						<?php _e( 'Description' ); ?><br />
						<textarea id="edit-menu-item-description-<?php echo $item_id; ?>" class="widefat edit-menu-item-description" rows="3" cols="20" name="menu-item-description[<?php echo $item_id; ?>]"><?php echo esc_html( $item->description ); // textarea_escaped ?></textarea>
						<span class="description"><?php _e('The description will be displayed in the menu if the current theme supports it.'); ?></span>
					</label>
				</p>

				<p class="field-move hide-if-no-js description description-wide">
					<label>
						<span><?php _e( 'Move' ); ?></span>
						<a href="#" class="menus-move menus-move-up" data-dir="up"><?php _e( 'Up one' ); ?></a>
						<a href="#" class="menus-move menus-move-down" data-dir="down"><?php _e( 'Down one' ); ?></a>
						<a href="#" class="menus-move menus-move-left" data-dir="left"></a>
						<a href="#" class="menus-move menus-move-right" data-dir="right"></a>
						<a href="#" class="menus-move menus-move-top" data-dir="top"><?php _e( 'To the top' ); ?></a>
					</label>
				</p>

				<div class="menu-item-actions description-wide submitbox">
					<?php if( 'custom' != $item->type && $original_title !== false ) : ?>
						<p class="link-to-original">
							<?php printf( __('Original: %s'), '<a href="' . esc_attr( $item->url ) . '">' . esc_html( $original_title ) . '</a>' ); ?>
						</p>
					<?php endif; ?>
					<a class="item-delete submitdelete deletion" id="delete-<?php echo $item_id; ?>" href="<?php
					echo wp_nonce_url(
						add_query_arg(
							array(
								'action' => 'delete-menu-item',
								'menu-item' => $item_id,
							),
							admin_url( 'nav-menus.php' )
						),
						'delete-menu_item_' . $item_id
					); ?>"><?php _e( 'Remove' ); ?></a> <span class="meta-sep hide-if-no-js"> | </span> <a class="item-cancel submitcancel hide-if-no-js" id="cancel-<?php echo $item_id; ?>" href="<?php echo esc_url( add_query_arg( array( 'edit-menu-item' => $item_id, 'cancel' => time() ), admin_url( 'nav-menus.php' ) ) );
						?>#menu-item-settings-<?php echo $item_id; ?>"><?php _e('Cancel'); ?></a>
				</div>

				<input class="menu-item-data-db-id" type="hidden" name="menu-item-db-id[<?php echo $item_id; ?>]" value="<?php echo $item_id; ?>" />
				<input class="menu-item-data-object-id" type="hidden" name="menu-item-object-id[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $item->object_id ); ?>" />
				<input class="menu-item-data-object" type="hidden" name="menu-item-object[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $item->object ); ?>" />
				<input class="menu-item-data-parent-id" type="hidden" name="menu-item-parent-id[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $item->menu_item_parent ); ?>" />
				<input class="menu-item-data-position" type="hidden" name="menu-item-position[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $item->menu_order ); ?>" />
				<input class="menu-item-data-type" type="hidden" name="menu-item-type[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $item->type ); ?>" />
			</div><!-- .menu-item-settings-->
			<ul class="menu-item-transport"></ul>
		<?php
		$output .= ob_get_clean();
	}
}

register_activation_hook( __FILE__, array( $automaticSubmenu, 'activate' ) );
register_deactivation_hook( __FILE__, array( $automaticSubmenu, 'deactivate' ) );