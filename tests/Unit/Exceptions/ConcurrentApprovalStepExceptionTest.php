<?php

use App\Exceptions\ConcurrentApprovalStepException;

uses()->group('my-approvals');

it('uses the translated default message', function () {
    app()->setLocale('en');

    expect((new ConcurrentApprovalStepException)->getMessage())
        ->toBe(__('approval.concurrent_step'))
        ->not->toBe('approval.concurrent_step');
});

it('uses the translated message in Indonesian', function () {
    app()->setLocale('id');

    expect((new ConcurrentApprovalStepException)->getMessage())
        ->toBe(__('approval.concurrent_step'))
        ->not->toBe('approval.concurrent_step');

    app()->setLocale('en');
});

it('renders a 409 JSON response with the message', function () {
    $response = (new ConcurrentApprovalStepException('custom'))->render();

    expect($response->getStatusCode())->toBe(409)
        ->and($response->getData(true))->toBe(['message' => 'custom']);
});
