<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

namespace TVA\Drip\Trigger;

use TVA\Drip\Campaign;
use TVA\Drip\Schedule\Utils;

/**
 * Class Base
 *
 * @package TVA\Drip\Trigger
 */
class Base {

	use Utils;

	/**
	 * @var null|\TVA\Drip\Schedule\Non_Repeating
	 */
	protected $schedule = null;

	/**
	 * @var string
	 */
	protected $datetime = '';

	/**
	 * @var Campaign
	 */
	protected $campaign;

	/**
	 * @var string
	 */
	const NAME = 'base';

	/**
	 * @var string
	 */
	const USER_META_KEY = '';

	/**
	 * @var string
	 */
	const EVENT = '';

	/**
	 * @var array
	 */
	protected static $cache = [];

	/**
	 * Class constructor
	 *
	 * @param array    $data
	 * @param Campaign $campaign
	 */
	public function __construct( $data, $campaign ) {

		$this->campaign = $campaign;

		foreach ( $data as $key => $value ) {

			if ( $key === 'schedule' ) {
				$schedule_type = empty( $value['type'] ) ? 'specific' : $value['type'];

				$this->{$key} = \TVA\Drip\Schedule\Base::factory( $value, $schedule_type );
			} elseif ( property_exists( $this, $key ) ) {
				$this->{$key} = $value;
			}
		}
	}

	/**
	 * Factory method for class
	 *
	 * @param array    $params
	 * @param Campaign $campaign
	 *
	 * @return \Base|\Specific_Date_Time_Interval|\Time_After_Purchase|\Time_After_First_Lesson
	 */
	public static function factory( $params, $campaign ) {
		$type = $params['id'];

		switch ( $type ) {
			case 'datetime':
				$class_name = __NAMESPACE__ . '\Specific_Date_Time_Interval';
				break;
			case 'purchase':
				$class_name = __NAMESPACE__ . '\Time_After_Purchase';
				break;
			case 'first-lesson':
				$class_name = __NAMESPACE__ . '\Time_After_First_Lesson';
				break;
			case 'automator':
				$class_name = __NAMESPACE__ . '\Automator';
				break;
			case 'course-content':
				$class_name = __NAMESPACE__ . '\Course_Content';
				break;
			case 'tqb_result':
				$class_name = '\TVA\TQB\Drip\Trigger\Result';
				break;
			case 'assessment':
				$class_name = __NAMESPACE__ . '\Assessment';
				break;
			case 'video-progress':
				$class_name = __NAMESPACE__ . '\Video_Progress';
				break;
			default:
				$class_name = __NAMESPACE__ . '\Base';
				break;
		}

		return new $class_name( $params, $campaign );
	}

	/**
	 * Returns true if the trigger conditions are met for a specific product
	 *
	 * @param int $product_id
	 * @param int $post_id
	 *
	 * @return boolean
	 */
	public function is_valid( $product_id, $post_id ) {
		if ( empty( $this->schedule ) || empty( $this->datetime ) ) {
			return false;
		}

		return $this->schedule->get_next_occurrence( static::get_datetime( $this->datetime ) ) < current_datetime();
	}

	/**
	 * Returns the moment when the trigger will unlock the content for the active customer,
	 * or null if no future unlock date is computable for this trigger type.
	 *
	 * Used by the drip unlock-date display shortcode. Only the three time-based triggers
	 * produce a date; all event-based triggers return null via this default.
	 *
	 * @return \DateTimeInterface|null
	 */
	public function get_unlock_timestamp(): ?\DateTimeInterface {
		if ( empty( $this->schedule ) ) {
			return null;
		}
		if ( static::NAME === 'first-lesson' ) {
			$customer = $this->get_customer();
			$anchor   = $customer instanceof \TVA_Customer ? $customer->get_begin_course_timestamp( $this->campaign->get_course_id() ) : false;
			$ts       = $anchor ? $this->schedule->get_next_occurrence( $anchor ) : null;
			return ( $ts && $ts > current_datetime() ) ? $ts : null;
		}
		if ( static::NAME === 'datetime' ) {
			$occurrence = property_exists( $this, 'occurrence' ) ? $this->occurrence : '';
			return $occurrence === 'after' ? $this->schedule->get_next_occurrence() : null;
		}
		// Generic Base instance — produced by Campaign::compute_trigger() for lessons 2+ in a
		// "specific datetime + recurring schedule" campaign (e.g., start May 1, then every week).
		// The campaign's unlock_date is the anchor, the schedule carries an interval_number that
		// has been multiplied by the lesson's content_index, so get_next_occurrence(anchor)
		// returns "anchor + (content_index × interval) " — i.e., the right unlock moment per
		// lesson position. Mirrors the logic of Base::is_valid() above.
		if ( static::NAME === 'base' && ! empty( $this->datetime ) ) {
			$ts = $this->schedule->get_next_occurrence( static::get_datetime( $this->datetime ) );
			return ( $ts && $ts > current_datetime() ) ? $ts : null;
		}
		return null;
	}

	/**
	 * Returns true if there is an event scheduled for the campaign parameters
	 *
	 * @param array $args
	 *
	 * @return bool
	 */
	public function is_event_scheduled( $args = array() ) {
		return wp_get_scheduled_event( static::EVENT, $args ) !== false;
	}

	/**
	 * Prepare the parameters for scheduling an event
	 *
	 * @param int                            $product_id
	 * @param int                            $post_id
	 * @param int                            $customer_id
	 * @param \DateTimeInterface|string|null $from_date date when to start the calculation. Defaults to the current date / time
	 */
	public function schedule_event( $product_id, $post_id, $customer_id = null, $from_date = null ) {
		$args = [
			(int) $product_id,
			(int) $this->campaign->ID,
			(int) $post_id,
		];
		if ( $customer_id ) {
			$args[] = $customer_id;
		}

		if ( $this->is_event_scheduled( $args ) ) {
			/**
			 * This means that a hook is already registered with these arguments
			 */
			return;
		}

		$next_occurrence = $this->schedule->get_next_occurrence( $from_date );

		/* do not schedule an event that's in the past */
		if ( ! $next_occurrence || current_datetime() >= $next_occurrence ) {
			return;
		}

		/* at this point, we can schedule the event at the corresponding timestamp */
		wp_schedule_single_event( $next_occurrence->getTimestamp(), static::EVENT, $args );
	}

	/**
	 * @return \TVA_Course_V2
	 */
	protected function get_course() {
		if ( empty( static::$cache['course'] ) ) {
			static::$cache['course'] = new \TVA_Course_V2( $this->campaign->get_course_id() );
		}

		return static::$cache['course'];
	}

	/**
	 * @return \TVA_Customer
	 */
	public function get_customer() {
		return $this->campaign->get_customer();
	}

	/**
	 * @return \TVA_User
	 */
	public function get_tva_user() {
		return new \TVA_User( $this->campaign->get_customer()->get_id() );
	}

	/**
	 * @return string The trigger id
	 */
	public function get_id() {
		return static::NAME;
	}

	/**
	 * Computes the dynamic meta key that marks the user that the trigger is valid
	 * Used for user based triggers
	 *
	 * @param int $post_id
	 * @param int $campaign_id
	 *
	 * @return string
	 */
	public static function get_user_meta_key( $post_id, $campaign_id ) {
		return sprintf( static::USER_META_KEY, $post_id, $campaign_id );
	}

	/**
	 * Marks this trigger as completed for the active customer
	 *
	 * @param int $post_id
	 */
	public function mark_user_completed( $post_id ) {
		update_user_meta( $this->get_customer()->get_id(), static::get_user_meta_key( $post_id, $this->campaign->ID ), 1 );
	}

	/**
	 * Get the original DateTime object representing the time at which a cron event has been scheduled.
	 * This will only be used for evergreen events
	 *
	 * @param array $cron_data
	 *
	 * @return \DateTime|\DateTimeImmutable|null
	 */
	public function get_original_event_date( $cron_data ) {
		/*
		 * $cron_data has a key called `args` with the following contents:
		 *
		 * $args[0] = product_id
		 * args[1] = campaign_id
		 * args[2] = post_id ( lesson/module )
		 * $args[3] = customer_id
		 */

		if ( empty( $cron_data['args'][2] ) || empty( $cron_data['args'][3] ) ) {
			return null;
		}

		$courses  = get_the_terms( $cron_data['args'][1], \TVA_Const::COURSE_TAXONOMY );
		$customer = new \TVA_Customer( $cron_data['args'][3] );

		if ( empty( $courses ) || ! $customer->get_id() ) {
			return null;
		}

		$args = [
			'product_id'  => $cron_data['args'][0],
			'campaign_id' => $cron_data['args'][1],
			'course_id'   => $courses[0]->term_id,
			'post_id'     => $cron_data['args'][2],
			'user_id'     => $cron_data['args'][3],
		];

		return $this->_compute_original_event_date( $args );
	}

	/**
	 * @param array $args
	 *
	 * @return null|\DateTime|\DateTimeImmutable
	 *
	 * @see Base::get_original_event_date()
	 */
	protected function _compute_original_event_date( $args ) {
	}

}
