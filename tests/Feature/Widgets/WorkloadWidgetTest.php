<?php

use Eloquage\FilamentHorizon\Services\HorizonApi;
use Eloquage\FilamentHorizon\Widgets\WorkloadWidget;

beforeEach(function () {
    $this->api = Mockery::mock(HorizonApi::class);
    app()->instance(HorizonApi::class, $this->api);
});

it('returns workload in view data', function () {
    $workload = [
        ['name' => 'default', 'length' => 10, 'wait' => 5],
        ['name' => 'high', 'length' => 5, 'wait' => 2],
    ];

    $this->api->shouldReceive('getWorkload')->andReturn($workload);

    $widget = new WorkloadWidget;
    $reflection = new ReflectionClass($widget);
    $method = $reflection->getMethod('getViewData');
    $method->setAccessible(true);
    $viewData = $method->invoke($widget);

    expect($viewData)->toBeArray();
    expect($viewData['workload'])->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($viewData['workload'])->toHaveCount(2);
});

it('formats wait time in workload', function () {
    $workload = [
        ['name' => 'default', 'length' => 10, 'wait' => 30],
        ['name' => 'high', 'length' => 5, 'wait' => 120],
    ];

    $this->api->shouldReceive('getWorkload')->andReturn($workload);

    $widget = new WorkloadWidget;
    $reflection = new ReflectionClass($widget);
    $method = $reflection->getMethod('getViewData');
    $method->setAccessible(true);
    $viewData = $method->invoke($widget);

    expect($viewData['workload']->first()['wait_formatted'])->toBe('30s');
    expect($viewData['workload']->last()['wait_formatted'])->toBeString();
});

it('handles zero wait time', function () {
    $workload = [
        ['name' => 'default', 'length' => 10, 'wait' => 0],
    ];

    $this->api->shouldReceive('getWorkload')->andReturn($workload);

    $widget = new WorkloadWidget;
    $reflection = new ReflectionClass($widget);
    $method = $reflection->getMethod('getViewData');
    $method->setAccessible(true);
    $viewData = $method->invoke($widget);

    expect($viewData['workload']->first()['wait_formatted'])->toBe('0s');
});

it('handles empty workload', function () {
    $this->api->shouldReceive('getWorkload')->andReturn([]);

    $widget = new WorkloadWidget;
    $reflection = new ReflectionClass($widget);
    $method = $reflection->getMethod('getViewData');
    $method->setAccessible(true);
    $viewData = $method->invoke($widget);

    expect($viewData['workload'])->toBeEmpty();
});

it('humanizes time correctly', function () {
    $widget = new WorkloadWidget;
    $reflection = new ReflectionClass($widget);
    $method = $reflection->getMethod('humanizeTime');
    $method->setAccessible(true);

    expect($method->invoke($widget, 0))->toBe('0s');
    expect($method->invoke($widget, 30))->toBe('30s');
    expect($method->invoke($widget, 120))->toBeString();
});

it('has correct polling interval', function () {
    $widget = new WorkloadWidget;
    $reflection = new ReflectionClass(WorkloadWidget::class);
    $method = $reflection->getMethod('getPollingInterval');
    $method->setAccessible(true);
    expect($method->invoke($widget))->toBe('5s');
});

it('has full column span', function () {
    $widget = new WorkloadWidget;
    expect($widget->getColumnSpan())->toBe('full');
});

afterEach(function () {
    Mockery::close();
});
