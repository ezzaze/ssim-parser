<?php

namespace Ezzaze\SsimParser\Regexes;

class Version3
{
    const RECORD_TYPE = '/(?P<record_type>3)/';
    const OPERATIONAL_SUFFIX = '/(?P<operational_suffix>.{1})/';
    const AIRLINE_DESIGNATOR = '/(?P<airline_designator>.{3})/';
    const FLIGHT_NUMBER = '/(?P<flight_number>.{4})/';
    const ITINERARY_VARIATION_IDENTIFIER = '/(?P<itinerary_variation_identifier>.{2})/';
    const LEG_SEQUENCE_NUMBER = '/(?P<leg_sequence_number>.{2})/';
    const SERVICE_TYPE = '/(?P<service_type>.{1})/';
    const OPERATION_START_DATE = '/(?P<operation_start_date>.{7})/';
    const OPERATION_END_DATE = '/(?P<operation_end_date>.{7})/';
    const OPERATION_DAYS_OF_WEEK = '/(?P<operation_days_of_week>.{7})/';
    const FREQUENCY_RATE = '/(?P<frequency_rate>.{1})/';
    const DEPARTURE_STATION = '/(?P<departure_station>.{3})/';
    const PASSENGER_DEPARTURE_TIME = '/(?P<passenger_departure_time>.{4})/';
    const AIRCRAFT_DEPARTURE_TIME = '/(?P<aircraft_departure_time>.{4})/';
    const UTC_LOCAL_DEPARTURE_TIME_VARIANT = '/(?P<utc_local_departure_time_variant>.{5})/';
    const PASSENGER_TERMINAL_DEPARTURE = '/(?P<passenger_terminal_departure>.{2})/';
    const ARRIVAL_STATION = '/(?P<arrival_station>.{3})/';
    const PASSENGER_ARRIVAL_TIME = '/(?P<passenger_arrival_time>.{4})/';
    const AIRCRAFT_ARRIVAL_TIME = '/(?P<aircraft_arrival_time>.{4})/';
    const UTC_LOCAL_ARRIVAL_TIME_VARIANT = '/(?P<utc_local_arrival_time_variant>.{5})/';
    const PASSENGER_TERMINAL_ARRIVAL = '/(?P<passenger_terminal_arrival>.{2})/';
    const AIRCRAFT_TYPE = '/(?P<aircraft_type>.{3})/';
    const PLACEHOLDER_0 = '/(?P<placeholder_0>.{20})/';
    const PASSENGER_RESERVATIONS_BOOKING = '/(?P<passenger_reservations_booking>.{5})/';
    const MEAL_SERVICE_NOTE = '/(?P<meal_service_note>.{10})/';
    const JOINT_OPERATION_AIRLINE = '/(?P<joint_operation_airline>.{9})/';
    const PLACEHOLDER_1 = '/(?P<placeholder_1>.{2})/';
    const SECURE_FLIGHT_INDICATOR = '/(?P<secure_flight_indicator>.{1})/';
    const PLACEHOLDER_2 = '/(?P<placeholder_2>.{5})/';
    const ITINERARY_VARIATION_ID = '/(?P<itinerary_variation_id>.{1})/';
    const AIRCRAFT_OWNER = '/(?P<aircraft_owner>.{3})/';
    const COCKPIT_CREW_EMPLOYER = '/(?P<cockpit_crew_employer>.{3})/';
    const CABIN_CREW_EMPLOYER = '/(?P<cabin_crew_employer>.{3})/';
    const AIRLINE_DESIGNATOR_ = '/(?P<airline_designator_>.{3})/';
    const INBOUND_FLIGHT_NUMBER = '/(?P<inbound_flight_number>.{4})/';
    const AIRCRAFT_ROTATION_LAYOVER = '/(?P<aircraft_rotation_layover>.{1})/';
    const OPERATIONAL_SUFFIX_ = '/(?P<operational_suffix_>.{1})/';
    const PLACEHOLDER_3 = '/(?P<placeholder_3>.{1})/';
    const FLIGHT_TRANSIT_LAYOVER = '/(?P<flight_transit_layover>.{1})/';
    const OPERATING_AIRLINE_DISCLOSURE = '/(?P<operating_airline_disclosure>.{1})/';
    const TRAFFIC_RESTRICTION_CODE = '/(?P<traffic_restriction_code>.{11})/';
    const PLACEHOLDER_4 = '/(?P<placeholder_4>.{12})/';
    const AIRCRAFT_CONFIGURATION_VERSION = '/(?P<aircraft_configuration_version>.{20})/';
    const DATE_VARIATION = '/(?P<date_variation>.{2})/';
    const RECORD_SERIAL_NUMBER = '/(?P<record_serial_number>.{6})/';

    protected array $hiddenAttributes = [
        self::PLACEHOLDER_0,
        self::PLACEHOLDER_1,
        self::PLACEHOLDER_2,
        self::PLACEHOLDER_3,
        self::PLACEHOLDER_4,
        self::AIRLINE_DESIGNATOR_,
        self::INBOUND_FLIGHT_NUMBER,
        self::MEAL_SERVICE_NOTE,
        self::JOINT_OPERATION_AIRLINE,
        self::SECURE_FLIGHT_INDICATOR,
    ];

    public function getHiddenAttributes(): array
    {
        return $this->hiddenAttributes;
    }
}
