<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Workflow tests trigger real notifications; an unfaked HTTP call would
        // send a live WhatsApp message with the credentials in the local .env.
        Http::preventStrayRequests();
    }
}
