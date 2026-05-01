<?php

/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

namespace TVA\Drip;

use TVA\TTB\Check;

class Drip {

	/**
	 * @return array[]
	 *
	 * @codeCoverageIgnore
	 */
	public static function get_campaign_types() {
		$types = array(
			array(
				'type'     => 'scheduled-repeating',
				'label'    => __( 'Scheduled repeating', 'thrive-apprentice' ),
				'desc'     => __( 'Unlock content at consistent intervals after a scheduled start date', 'thrive-apprentice' ),
				'longdesc' => __( 'Scheduled repeating campaigns unlock content at consistent intervals, such as one lesson or module per week. Unlike evergreen campaigns, the content is unlocked at the same time for everyone starting from the scheduled date and time you choose.', 'thrive-apprentice' ),
			),
			array(
				'type'     => 'evergreen-repeating',
				'label'    => __( 'Evergreen repeating', 'thrive-apprentice' ),
				'desc'     => __( 'Unlock content at consistent intervals for each member', 'thrive-apprentice' ),
				'longdesc' => __( 'Evergreen repeating campaigns unlock content at consistent intervals, such as one lesson or module per week. Each member has their own unlock timeline depending on the trigger you define (when the user purchases the product, or starts the course).', 'thrive-apprentice' ),
			),
			array(
				'type'     => 'day-of-week',
				'label'    => __( 'Day of the week or month', 'thrive-apprentice' ),
				'desc'     => __( 'Unlock content on a specific week day or day of the month', 'thrive-apprentice' ),
				'longdesc' => __( 'Unlock content on a specific day of the week such as every Monday, a day of the month such as every second Thursday, or a monthly calendar date such as the 15th of each month.', 'thrive-apprentice' ),
			),
			array(
				'type'     => 'live-launch',
				'label'    => __( 'Drip on specific dates', 'thrive-apprentice' ),
				'desc'     => __( 'Unlock content on specific calendar dates that you can customize', 'thrive-apprentice' ),
				'longdesc' => __( 'This campaign gives you the freedom to unlock content at custom intervals. For example, you may want to unlock module 1 on the 12th February and then module 2 on the 21st February. You can choose the exact unlock dates for each piece of content in your course.', 'thrive-apprentice' ),
			),
			array(
				'type'     => 'sequential',
				'label'    => __( 'Sequential unlock', 'thrive-apprentice' ),
				'desc'     => __( 'Force users to unlock the content of their course in order', 'thrive-apprentice' ),
				'longdesc' => __( 'The sequential unlock Drip template means your members must progress through your course in order and cannot access a lesson until they mark the one before it as complete. You can add additional drip rules per-lesson if you`d like.', 'thrive-apprentice' ),
			),
			array(
				'type'         => 'automator',
				'disabled'     => ! Check::automator(),
				'label'        => __( 'Thrive Automator unlock', 'thrive-apprentice' ),
				'desc'         => __( 'Use custom event triggers and 3rd party integrations to unlock content', 'thrive-apprentice' ),
				'heading'      => __( 'We have sunsetted Thrive Automator.', 'thrive-apprentice' ),
				'longdesc'     => __( 'Automate the unlocking of your course content with ', 'thrive-apprentice' ),
				'link_autom'   => 'https://thrivethemes.com/uncanny-automator/',
				'link_text'    => __( 'Learn more', 'thrive-apprentice' ),
				'uncanny_text' => __( 'Uncanny Automator.', 'thrive-apprentice' ),
			),
			array(
				'type'          => 'custom',
				'label'         => __( 'Start from scratch', 'thrive-apprentice' ),
				'details_label' => __( 'Custom drip schedule', 'thrive-apprentice' ),
				'longdesc'      => __( 'Build your campaign from scratch. Choose your trigger event, set unlock intervals or enable custom unlock conditions for all of the content in your course', 'thrive-apprentice' ),
			),
		);

				// Update labels if Automator is not active
		if ( ! Check::automator() || is_plugin_active( 'uncanny-automator/uncanny-automator.php' ) ) {
			foreach ( $types as &$type ) {
				if ( $type['type'] === 'automator' ) {
					$type['label']    = __( 'Uncanny Automator', 'thrive-apprentice' );
					$type['desc']     = __( 'Use custom event triggers and 3rd party integrations to unlock content', 'thrive-apprentice' );
					$type['longdesc'] = __( 'The Uncanny Automator drip template allows you to lock your content without rules. You can then create custom unlock automations based on website events or 3rd party integrations.' );
				}
			}
		}

		return $types;
	}

	/**
	 * @return array[]
	 * @codeCoverageIgnore
	 */
	public static function get_content_triggers() {
		return array(
			array(
				'id'      => 'campaign',
				'name'    => __( 'Time after campaign trigger', 'thrive-apprentice' ),
				'summary' => __( 'Campaign trigger', 'thrive-apprentice' ),
			),
			array(
				'id'      => 'datetime',
				'name'    => __( 'At a specific date/time', 'thrive-apprentice' ),
				'summary' => __( 'Specific time and date', 'thrive-apprentice' ),
			),
			array(
				'id'      => 'purchase',
				'name'    => __( 'Time after user purchases product', 'thrive-apprentice' ),
				'summary' => __( 'Purchase', 'thrive-apprentice' ),
			),
			array(
				'id'       => 'tqb_result',
				'name'     => __( 'Thrive Quiz Result', 'thrive-apprentice' ),
				'summary'  => __( 'Quiz result', 'thrive-apprentice' ),
				'disabled' => ! \TVA\TQB\tva_tqb_integration()->is_quiz_builder_active(),
			),
			array(
				'id'      => 'first-lesson',
				'name'    => __( 'Time after user starts the course', 'thrive-apprentice' ),
				'summary' => __( 'Accesses the course', 'thrive-apprentice' ),
			),
			array(
				'id'       => 'automator',
				'name'     => __( 'Automation Action', 'thrive-apprentice' ),
				'summary'  => __( 'Action', 'thrive-apprentice' ),
				'disabled' => ! Check::automator(),
			),
			array(
				'id'      => 'course-content',
				'name'    => __( 'When course content is marked as complete', 'thrive-apprentice' ),
				'summary' => __( 'Course content is completed', 'thrive-apprentice' ),
			),
			array(
				'id'      => 'assessment',
				'name'    => __( 'Assessment status', 'thrive-apprentice' ),
				'summary' => __( 'Assessment', 'thrive-apprentice' ),
			),
			array(
				'id'      => 'video-progress',
				'name'    => __( 'Video watched to completion', 'thrive-apprentice' ),
				'summary' => __( 'Video progress', 'thrive-apprentice' ),
			),
		);
	}
}
