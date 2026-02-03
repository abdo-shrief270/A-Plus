<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class ApiResponseTraitTest extends TestCase
{
    use ApiResponseTrait;

    public function test_success_response_structure()
    {
        $data = ['key' => 'value'];
        $message = 'Success message';

        $response = $this->successResponse($data, $message);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $content = $response->getData(true);

        $this->assertTrue($content['status']);
        $this->assertEquals($message, $content['message']);
        $this->assertEquals($data, $content['data']);
    }

    public function test_error_response_structure()
    {
        $message = 'Error message';
        $code = 400;

        $response = $this->errorResponse($message, $code);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals($code, $response->getStatusCode());

        $content = $response->getData(true);

        $this->assertFalse($content['status']);
        $this->assertEquals($message, $content['message']);
        $this->assertArrayNotHasKey('errors', $content);
    }

    public function test_error_response_with_errors()
    {
        $message = 'Validation Error';
        $errors = ['field' => ['Error detail']];
        $code = 422;

        $response = $this->errorResponse($message, $code, $errors);

        $content = $response->getData(true);

        $this->assertFalse($content['status']);
        $this->assertEquals($errors, $content['errors']);
    }
}
