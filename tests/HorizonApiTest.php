<?php

use Eloquage\FilamentHorizon\Services\HorizonApi;
use Illuminate\Support\Collection;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Contracts\WorkloadRepository;

it('can be instantiated', function () {
    $mockJobRepository = Mockery::mock(JobRepository::class);
    $mockMetricsRepository = Mockery::mock(MetricsRepository::class);
    $mockTagRepository = Mockery::mock(TagRepository::class);
    $mockWorkloadRepository = Mockery::mock(WorkloadRepository::class);
    $mockSupervisorRepository = Mockery::mock(SupervisorRepository::class);
    $mockMasterSupervisorRepository = Mockery::mock(MasterSupervisorRepository::class);
    $mockBatchRepository = Mockery::mock(\Illuminate\Bus\BatchRepository::class);

    $api = new HorizonApi(
        $mockJobRepository,
        $mockMetricsRepository,
        $mockTagRepository,
        $mockWorkloadRepository,
        $mockSupervisorRepository,
        $mockMasterSupervisorRepository,
        $mockBatchRepository,
    );

    expect($api)->toBeInstanceOf(HorizonApi::class);
});

it('can get workload', function () {
    $mockJobRepository = Mockery::mock(JobRepository::class);
    $mockMetricsRepository = Mockery::mock(MetricsRepository::class);
    $mockTagRepository = Mockery::mock(TagRepository::class);
    $mockWorkloadRepository = Mockery::mock(WorkloadRepository::class);
    $mockSupervisorRepository = Mockery::mock(SupervisorRepository::class);
    $mockMasterSupervisorRepository = Mockery::mock(MasterSupervisorRepository::class);
    $mockBatchRepository = Mockery::mock(\Illuminate\Bus\BatchRepository::class);

    $mockWorkloadRepository->shouldReceive('get')->andReturn([
        ['name' => 'default', 'length' => 10, 'wait' => 5, 'processes' => 3],
        ['name' => 'high', 'length' => 5, 'wait' => 2, 'processes' => 2],
    ]);

    $api = new HorizonApi(
        $mockJobRepository,
        $mockMetricsRepository,
        $mockTagRepository,
        $mockWorkloadRepository,
        $mockSupervisorRepository,
        $mockMasterSupervisorRepository,
        $mockBatchRepository,
    );

    $workload = $api->getWorkload();

    expect($workload)->toBeArray();
    expect($workload)->toHaveCount(2);
});

it('can get monitored tags', function () {
    $mockJobRepository = Mockery::mock(JobRepository::class);
    $mockMetricsRepository = Mockery::mock(MetricsRepository::class);
    $mockTagRepository = Mockery::mock(TagRepository::class);
    $mockWorkloadRepository = Mockery::mock(WorkloadRepository::class);
    $mockSupervisorRepository = Mockery::mock(SupervisorRepository::class);
    $mockMasterSupervisorRepository = Mockery::mock(MasterSupervisorRepository::class);
    $mockBatchRepository = Mockery::mock(\Illuminate\Bus\BatchRepository::class);

    $mockTagRepository->shouldReceive('monitoring')->andReturn(['user:1', 'order:123']);
    $mockTagRepository->shouldReceive('count')->with('user:1')->andReturn(5);
    $mockTagRepository->shouldReceive('count')->with('failed:user:1')->andReturn(1);
    $mockTagRepository->shouldReceive('count')->with('order:123')->andReturn(10);
    $mockTagRepository->shouldReceive('count')->with('failed:order:123')->andReturn(0);

    $api = new HorizonApi(
        $mockJobRepository,
        $mockMetricsRepository,
        $mockTagRepository,
        $mockWorkloadRepository,
        $mockSupervisorRepository,
        $mockMasterSupervisorRepository,
        $mockBatchRepository,
    );

    $tags = $api->getMonitoredTags();

    expect($tags)->toBeInstanceOf(Collection::class);
    expect($tags)->toHaveCount(2);
});

afterEach(function () {
    Mockery::close();
});
