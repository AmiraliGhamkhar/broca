<?php

namespace App\Services\Sms;

use RuntimeException;

/**
 * Thrown when a transport was selected, reached, and answered with a failure.
 *
 * Distinct from a *misconfigured* or *disabled* SMS stack, which is a normal
 * state (a fresh install has no credentials) and is modelled as a failed
 * SmsResult instead. Only a real transport error throws, so callers can
 * separate "we could not reach the panel, try again" from "SMS is not set up".
 */
class SmsDeliveryException extends RuntimeException {}
