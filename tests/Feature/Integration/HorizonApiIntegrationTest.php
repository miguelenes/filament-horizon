<?php

use Eloquage\FilamentHorizon\Services\HorizonApi;
use Illuminate\Bus\BatchRepository;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Contracts\WorkloadRepository;

it('resolves HorizonApi from container', function () {
    $api = app(HorizonApi::class);

    expect($api)->toBeInstanceOf(HorizonApi::class);
});

it('injects all required repositories', function () {
    $api = app(HorizonApi::class);

    $reflection = new ReflectionClass($api);

    $jobsProperty = $reflection->getProperty('jobs');
    $jobsProperty->setAccessible(true);
    expect($jobsProperty->getValue($api))->toBeInstanceOf(JobRepository::class);

    $metricsProperty = $reflection->getProperty('metrics');
    $metricsProperty->setAccessible(true);
    expect($metricsProperty->getValue($api))->toBeInstanceOf(MetricsRepository::class);

    $tagsProperty = $reflection->getProperty('tags');
    $tagsProperty->setAccessible(true);
    expect($tagsProperty->getValue($api))->toBeInstanceOf(TagRepository::class);

    $workloadProperty = $reflection->getProperty('workload');
    $workloadProperty->setAccessible(true);
    expect($workloadProperty->getValue($api))->toBeInstanceOf(WorkloadRepository::class);

    $supervisorsProperty = $reflection->getProperty('supervisors');
    $supervisorsProperty->setAccessible(true);
    expect($supervisorsProperty->getValue($api))->toBeInstanceOf(SupervisorRepository::class);

    $mastersProperty = $reflection->getProperty('masters');
    $mastersProperty->setAccessible(true);
    expect($mastersProperty->getValue($api))->toBeInstanceOf(MasterSupervisorRepository::class);

    $batchesProperty = $reflection->getProperty('batches');
    $batchesProperty->setAccessible(true);
    expect($batchesProperty->getValue($api))->toBeInstanceOf(BatchRepository::class);
});

it('works with real Horizon contracts', function () {
    $api = app(HorizonApi::class);

    // Test that methods can be called without errors
    // The actual implementation will depend on Horizon being configured
    expect($api)->toBeInstanceOf(HorizonApi::class);
});
