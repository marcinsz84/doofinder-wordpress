<?php

namespace Doofinder\WP\Settings;

use Doofinder\WP\Multilanguage\Language_Plugin;
use Doofinder\WP\Multilanguage\No_Language_Plugin;
use Doofinder\WP\Post_Types;

defined( 'ABSPATH' ) or die();

/**
 * @property Language_Plugin $language
 */
trait Renderers {

	/**
	 * Display form for doofinder settings page.
	 *
	 * If language plugin is active, but no language is selected we'll prompt the user
	 * to select a language instead of displaying settings.
	 *
	 * @since 1.0.0
	 */
	private function render_html_settings_page() {
		if ( ( $this->language instanceof No_Language_Plugin ) || $this->language->get_active_language() ) {
			$this->render_html_settings();

			return;
		}

		$this->render_html_pick_language_prompt();
	}

	/**
	 * Display the tabs.
	 */
	private function render_html_tabs() {
		// URL to the current page, but without GET for the nav item.
		$base_url = add_query_arg(
			'page',
			self::$top_level_menu,
			admin_url( 'admin.php' )
		);

		?>

        <nav class="nav-tab-wrapper">
			<?php foreach ( self::$tabs as $id => $options ): ?>
				<?php

				$current_tab_url = add_query_arg( 'tab', $id, $base_url );

				$is_active = false;
				if (
					// Current tab is selected.
					( isset( $_GET['tab'] ) && $_GET['tab'] === $id )

					// No tab is selected, and current tab is the first one.
					|| ( ! isset( $_GET['tab'] ) && $id === array_keys( self::$tabs )[0] )
				) {
					$is_active = true;
				}

				?>

                <a
                        href="<?php echo $current_tab_url; ?>"
                        class="nav-tab <?php if ( $is_active ): ?>nav-tab-active<?php endif; ?>"
                >
					<?php echo $options['label']; ?>
                </a>

			<?php endforeach; ?>
        </nav>

		<?php
	}

	/**
	 * Display the settings.
	 */
	private function render_html_settings() {
		// only users that have access to wp settings can view this form
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// add update messages if doesn't exist
		$errors = get_settings_errors( 'doofinder_for_wp_messages' );

		if ( isset( $_GET['settings-updated'] ) && ! $this->in_2d_array( 'doofinder_for_wp_message', $errors ) ) {
			add_settings_error( 'doofinder_for_wp_messages', 'doofinder_for_wp_message',
				__( 'Settings Saved', 'doofinder_for_wp' ), 'updated' );
		}

		// show error/update messages
		settings_errors( 'doofinder_for_wp_messages' );
		get_settings_errors( 'doofinder_for_wp_messages' );

		?>

        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php $this->render_html_tabs(); ?>

            <form action="options.php" method="post">
				<?php

				settings_fields( self::$top_level_menu );
				$this->render_html_current_tab_id();
				do_settings_sections( self::$top_level_menu );
				submit_button( 'Save Settings' );

				?>
            </form>
        </div>

		<?php
	}

	/**
	 * Prompt the user to select a language.
	 */
	private function render_html_pick_language_prompt() {
		?>

        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

            <div class="notice notice-error">
                <p><?php _e( 'You have a multi-language plugin installed. Please choose language first to configure Doofinder.',
						'doofinder_for_wp' ); ?></p>
            </div>
        </div>

		<?php
	}

	/**
	 * Renders the hidden input containing the information which tab
     * is currently selected.
     *
     * We need to render the same group of settings when displaying
     * them to the user (via GET) and when processing the save action
     * (POST), otherwise validation will fail. Since we cannot know
     * from which tab the data was posted (there's not GET variables
     * in POST request), we'll submit it in the hidden field.
	 */
	private function render_html_current_tab_id() {
		$selected_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : array_keys( self::$tabs )[0];

	    ?>

        <input
                type="hidden"
                name="doofinder_for_wp_selected_tab"
                value="<?php echo $selected_tab; ?>"
        >

        <?php
    }

	/**
	 * Print HTML for the "API Key" option.
	 *
	 * @param string $option_name
	 */
	private function render_html_api_key( $option_name ) {
		$saved_value = get_option( $option_name );

		?>

        <span class="doofinder-tooltip"><span><?php _e( 'The secret token is used to authenticate requests. Don`t need to use eu1- or us1- prefix.',
					'doofinder_for_wp' ); ?></span></span>
        <input type="text"
               name="<?php echo $option_name; ?>"
               class="widefat"

			<?php if ( $saved_value ): ?>
                value="<?php echo $saved_value; ?>"
			<?php endif; ?>
        >

		<?php
	}
	
	/**
	 * Print HTML for the "API Host" option.
	 *
	 * @param string $option_name
	 */
	private function render_html_api_host( $option_name ) {
		$saved_value = get_option( $option_name );

		?>

        <span class="doofinder-tooltip"><span><?php _e( 'The API host should contain https://, ex. https://eu1-api.doofinder.com',
					'doofinder_for_wp' ); ?></span></span>
        <input type="text"
               name="<?php echo $option_name; ?>"
               class="widefat"

			<?php if ( $saved_value ): ?>
                value="<?php echo $saved_value; ?>"
			<?php endif; ?>
        >

		<?php
	}

	/**
	 * Print HTML for the "Search engine hash" option.
	 *
	 * @param string $option_name
	 */
	private function render_html_search_engine_hash( $option_name ) {
		$saved_value = get_option( $option_name );

		?>

        <span class="doofinder-tooltip"><span><?php _e( 'The Hash id of a search engine in your Doofinder Account.',
					'doofinder_for_wp' ); ?></span></span>

        <input type="text"
               name="<?php echo $option_name; ?>"
               class="widefat"

			<?php if ( $saved_value ): ?>
                value="<?php echo $saved_value; ?>"
			<?php endif; ?>
        >

		<?php
	}

	/**
	 * Print HTML for the "Disable debug mode" option.
	 *
	 * @param string $option_name
	 */
	private function render_html_disable_debug_mode( $option_name ) {
		$saved_value = get_option( $option_name );

		?>

		<span class="doofinder-tooltip"><span><?php _e( 'Checking this will make indexing possible on staging (it will disable the debug mode).',
					'doofinder_for_wp' ); ?></span></span>

		<input
			type="checkbox"
			name="<?php echo $option_name ?>"
			<?php if ( $saved_value ): ?>
				checked="checked"
			<?php endif ;?>
		>

		<?php
	}

	/**
	 * Print HTML with checkboxes where user can select
	 * which post types to index.
	 *
	 * @param string $option_name
	 */
	private function render_html_post_types_to_index( $option_name ) {
		// Saved list of post types.
		$saved_value = get_option( $option_name );

		// We later check in array, if option is empty,
		// then empty string is returned.
		if ( ! $saved_value ) {
			$saved_value = array();
		}

		?>
        <span class="doofinder-tooltip"><span><?php _e( 'You must reindex your content after changing this setting.',
					'doofinder_for_wp' ); ?></span></span>
		<?php

		// Output checkboxes with post types.
		$post_types = Post_Types::instance();
		foreach ( $post_types->get() as $post_type ) {
			$checked = array_key_exists( $post_type, $saved_value );
			if ( ! $saved_value && Post_Types::is_default( $post_type ) ) {
				$checked = true;
			}

			?>

            <label>
                <input type="checkbox"
                       name="<?php echo $option_name; ?>[<?php echo $post_type; ?>]"

					<?php if ( $checked ): ?>
                        checked
					<?php endif; ?>
                >

				<?php

				// Get full name of the post type.
				$post_type_object = get_post_type_object( $post_type );
				echo $post_type_object->labels->name;

				?>&nbsp;&nbsp;
            </label>

			<?php
		}
	}

	/**
	 * Render checkbox to enable/disable indexing categories.
	 *
	 * @param string $option_name
	 */
	private function render_html_index_categories( $option_name ) {
		?>

        <label>
            <input
                    type="checkbox"
                    name="<?php echo $option_name; ?>"

				<?php if ( get_option( $option_name ) ): ?>
                    checked="checked"
				<?php endif; ?>
            >

			<?php _e( 'Index Categories', 'doofinder_for_wp' ); ?>
        </label>

		<?php
	}

	/**
	 * Render checkbox to enable/disable indexing tags.
	 *
	 * @param string $option_name
	 */
	private function render_html_index_tags( $option_name ) {
		?>

        <label>
            <input
                    type="checkbox"
                    name="<?php echo $option_name; ?>"

				<?php if ( get_option( $option_name ) ): ?>
                    checked="checked"
				<?php endif; ?>
            >

			<?php _e( 'Index Tags', 'doofinder_for_wp' ); ?>
        </label>

		<?php
	}

	/**
	 * Render the inputs for Additional Attributes. This is a table
	 * of inputs and selects where the user can choose any additional
	 * fields to add to the exported data.
	 *
	 * @param string $option_name
	 */
	private function render_html_additional_attributes( $option_name ) {
		$saved_attributes = get_option( $option_name );

		?>

        <table class="doofinder-for-wp-attributes">
            <thead>
            <tr>
                <th><?php _e( 'Field', 'doofinder_for_wp' ); ?></th>
                <th><?php _e( 'Attribute', 'doofinder_for_wp' ); ?></th>
                <th><?php _e( 'Delete', 'doofinder_for_wp' ); ?></th>
            </tr>
            </thead>

            <tbody>
			<?php

            if (! empty($saved_attributes)) {
                foreach ($saved_attributes as $index => $attribute) {
                    $this->render_html_single_additional_attribute(
                        $option_name,
                        $index,
                        $attribute
                    );
                }
            }

			$this->render_html_single_additional_attribute(
				$option_name,
				'new'
			);

			?>
            </tbody>
        </table>

		<?php
	}

	/**
	 * Renders a single row representing additional attribute.
	 * A helper for "render_html_additional_attributes".
	 *
	 * @see Renderers::render_html_additional_attributes
	 *
	 * @param string $option_name
	 * @param string|int $index
	 * @param ?array $attribute
	 */
	private function render_html_single_additional_attribute( $option_name, $index, $attribute = null ) {
		$attributes = include 'attributes.php';

		?>

        <tr>
            <td>
                <input
                        type="text"
                        name="<?php echo $option_name; ?>[<?php echo $index; ?>][field]"

					<?php if ( $attribute ): ?>
                        value="<?php echo $attribute['field']; ?>"
					<?php endif; ?>
                />
            </td>

            <td>
                <select
                        name="<?php echo $option_name; ?>[<?php echo $index; ?>][attribute]"
                >
					<?php foreach ( $attributes as $id => $attr ): ?>
                        <option
                                value="<?php echo $id; ?>"

							<?php if ( $attribute && $attribute['attribute'] === $id ): ?>
                                selected="selected"
							<?php endif; ?>
                        >
							<?php echo $attr['label']; ?>
                        </option>
					<?php endforeach; ?>
                </select>
            </td>

            <td>
                <input
                        type="checkbox"
                        name="<?php echo $option_name; ?>[<?php echo $index; ?>][delete]"
                />
            </td>
        </tr>

		<?php
	}

	/**
	 * Render a checkbox allowing user to enable / disable the JS layer.
	 *
	 * @param string $option_name
	 */
	private function render_html_enable_js_layer( $option_name ) {
		$saved_value = get_option( $option_name );

		?>
        <span class="doofinder-tooltip"><span><?php _e( 'The JS Layer script will be added to your site\'s template.',
					'doofinder_for_wp' ); ?></span></span>
        <label>
            <input type="checkbox" name="<?php echo $option_name; ?>"
				<?php if ( $saved_value ): ?>
                    checked
				<?php endif; ?>
            >

			<?php _e( 'Enable JS Layer', 'doofinder_for_wp' ); ?>
        </label>

		<?php
	}

	/**
	 * Render a checkbox allowing user to enable / disable the JS layer.
	 *
	 * @param string $option_name
	 */
	private function render_html_load_js_layer_from_doofinder( $option_name ) {
		$saved_value = get_option( $option_name );

		?>
        <span class="doofinder-tooltip"><span><?php _e( 'The script is obtained from Doofinder servers instead of from the JS Layer Script field.',
					'doofinder_for_wp' ); ?></span></span>
        <label>
            <input type="checkbox" name="<?php echo $option_name; ?>"
				<?php if ( $saved_value ): ?>
                    checked
				<?php endif; ?>
            >

			<?php _e( 'Load JS Layer directly from Doofinder', 'doofinder_for_wp' ); ?>
        </label>

		<?php
	}

	/**
	 * Render the textarea containing Doofinder JS Layer code.
	 *
	 * @param string $option_name
	 */
	private function render_html_js_layer( $option_name ) {
		$saved_value = get_option( $option_name );

		?>
        <span class="doofinder-tooltip"><span><?php _e( 'Paste here the JS Layer code obtained from Doofinder.',
					'doofinder_for_wp' ); ?></span></span>
        <textarea name="<?php echo $option_name; ?>" class="widefat" rows="16"><?php

			if ( $saved_value ) {
				echo wp_unslash( $saved_value );
			}

			?></textarea>

		<?php
	}

	/**
	 * Render checkbox allowing users to enable/disable the Internal Search.
	 *
	 * @param string $option_name
	 */
	private function render_html_enable_internal_search( $option_name ) {
		$saved_value = get_option( $option_name );

		?>

        <span class="doofinder-tooltip"><span><?php _e( 'Enabling this setting will make WordPress use Doofinder internally for search.',
					'doofinder_for_wp' ); ?></span></span>
        <label>
            <input type="checkbox" name="<?php echo $option_name; ?>"
				<?php if ( $saved_value ): ?>
                    checked
				<?php endif; ?>
            >

			<?php _e( 'Enable Internal Search', 'doofinder_for_wp' ); ?>
        </label>

		<?php
	}

	/**
	 * Print the information that indexing is in progress.
	 *
	 * We cannot change some options (e.g. which post types to index)
	 * if we are already indexing.
	 */
	private function render_html_indexing_in_progress() {
		?>

        <i><?php _e( 'Indexing is in progress. Wait until indexing finishes before changing the settings.',
				'doofinder_for_wp' ); ?></i>

		<?php
	}
}
