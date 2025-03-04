<?php

namespace Tests\Unit;

use App\Services\SMS\LocalSMSService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LocalSMSServiceTest extends TestCase
{
    /**
     * Test sending SMS with local SMS service
     *
     * @return void
     */
    public function test_send_sms_with_local_provider()
    {
        // Mock the HTTP facade
        Http::fake([
            'example-sms-provider.com/api/send' => Http::response(['success' => true], 200),
        ]);

        // Create the service
        $smsService = new LocalSMSService();
        
        // Call the send method
        $result = $smsService->send('1234567890', 'Test message');
        
        // Assert the result was successful
        $this->assertTrue($result);
        
        // Assert the HTTP request was sent correctly
        Http::assertSent(function ($request) {
            return $request->url() == 'https://example-sms-provider.com/api/send' &&
                   $request->hasHeader('Content-Type', 'application/json') &&
                   isset($request->data()['message']) &&
                   $request->data()['message'] === 'Test message';
        });
    }
    
    /**
     * Test sending SMS with failed response
     *
     * @return void
     */
    public function test_send_sms_with_failure()
    {
        // Mock the HTTP facade to return an error
        Http::fake([
            'example-sms-provider.com/api/send' => Http::response(['success' => false, 'error' => 'Invalid credentials'], 401),
        ]);

        // Create the service
        $smsService = new LocalSMSService();
        
        // Call the send method
        $result = $smsService->send('1234567890', 'Test message');
        
        // Assert the result was unsuccessful
        $this->assertFalse($result);
    }
    
    /**
     * Test sending SMS with exception
     *
     * @return void
     */
    public function test_send_sms_with_exception()
    {
        // Mock the HTTP facade to throw an exception
        Http::fake(function () {
            throw new \Exception('Network error');
        });

        // Create the service
        $smsService = new LocalSMSService();
        
        // Call the send method
        $result = $smsService->send('1234567890', 'Test message');
        
        // Assert the result was unsuccessful
        $this->assertFalse($result);
    }
}
