<?php

use Eloquage\FilamentHorizon\Services\HorizonApi;
use Illuminate\Bus\BatchRepository;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Contracts\WorkloadRepository;
use Laravel\Horizon\Jobs\MonitorTag;
use Laravel\Horizon\Jobs\RetryFailedJob;
use Laravel\Horizon\Jobs\StopMonitoringTag;
use Laravel\Horizon\WaitTimeCalculator;

function createMockApi(): HorizonApi
{
    return new HorizonApi(
        Mockery::mock(JobRepository::class),
        Mockery::mock(MetricsRepository::class),
        Mockery::mock(TagRepository::class),
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        Mockery::mock(MasterSupervisorRepository::class),
        Mockery::mock(BatchRepository::class),
    );
}

it('can be instantiated', function () {
    $api = createMockApi();

    expect($api)->toBeInstanceOf(HorizonApi::class);
});

it('can get stats', function () {
    $mockJobRepository = Mockery::mock(JobRepository::class);
    $mockMetricsRepository = Mockery::mock(MetricsRepository::class);
    $mockTagRepository = Mockery::mock(TagRepository::class);
    $mockWorkloadRepository = Mockery::mock(WorkloadRepository::class);
    $mockSupervisorRepository = Mockery::mock(SupervisorRepository::class);
    $mockMasterSupervisorRepository = Mockery::mock(MasterSupervisorRepository::class);
    $mockBatchRepository = Mockery::mock(BatchRepository::class);

    $mockJobRepository->shouldReceive('countRecentlyFailed')->andReturn(5);
    $mockJobRepository->shouldReceive('countRecent')->andReturn(100);
    $mockMetricsRepository->shouldReceive('jobsProcessedPerMinute')->andReturn(50);
    $mockMetricsRepository->shouldReceive('queueWithMaximumRuntime')->andReturn('default');
    $mockMetricsRepository->shouldReceive('queueWithMaximumThroughput')->andReturn('high');
    $mockWorkloadRepository->shouldReceive('get')->andReturn([
        ['name' => 'default'],
        ['name' => 'high'],
    ]);
    $mockSupervisorRepository->shouldReceive('all')->andReturn([
        (object) ['processes' => ['default' => 2, 'high' => 1]],
    ]);
    $mockMasterSupervisorRepository->shouldReceive('all')->andReturn([
        (object) ['status' => 'running'],
    ]);

    $waitCalculator = Mockery::mock(WaitTimeCalculator::class);
    $waitCalculator->shouldReceive('calculate')->andReturn(['default:queue' => 10]);
    app()->instance(WaitTimeCalculator::class, $waitCalculator);

    Config::set('horizon.trim.recent_failed', 10080);
    Config::set('horizon.trim.recent', 60);

    $api = new HorizonApi(
        $mockJobRepository,
        $mockMetricsRepository,
        $mockTagRepository,
        $mockWorkloadRepository,
        $mockSupervisorRepository,
        $mockMasterSupervisorRepository,
        $mockBatchRepository,
    );

    $stats = $api->getStats();

    expect($stats)->toBeArray();
    expect($stats)->toHaveKeys(['failedJobs', 'jobsPerMinute', 'pausedMasters', 'periods', 'processes', 'queueWithMaxRuntime', 'queueWithMaxThroughput', 'recentJobs', 'status', 'totalQueues', 'wait']);
    expect($stats['failedJobs'])->toBe(5);
    expect($stats['recentJobs'])->toBe(100);
    expect($stats['jobsPerMinute'])->toBe(50);
    expect($stats['processes'])->toBe(3);
    expect($stats['status'])->toBe('running');
    expect($stats['totalQueues'])->toBe(2);
});

it('can get workload', function () {
    $mockWorkloadRepository = Mockery::mock(WorkloadRepository::class);
    $mockWorkloadRepository->shouldReceive('get')->andReturn([
        ['name' => 'high', 'length' => 5, 'wait' => 2, 'processes' => 2],
        ['name' => 'default', 'length' => 10, 'wait' => 5, 'processes' => 3],
    ]);

    $api = new HorizonApi(
        Mockery::mock(JobRepository::class),
        Mockery::mock(MetricsRepository::class),
        Mockery::mock(TagRepository::class),
        $mockWorkloadRepository,
        Mockery::mock(SupervisorRepository::class),
        Mockery::mock(MasterSupervisorRepository::class),
        Mockery::mock(BatchRepository::class),
    );

    $workload = $api->getWorkload();

    expect($workload)->toBeArray();
    expect($workload)->toHaveCount(2);
    expect($workload[0]['name'])->toBe('default'); // Should be sorted
    expect($workload[1]['name'])->toBe('high');
});

it('can get empty workload', function () {
    $mockWorkloadRepository = Mockery::mock(WorkloadRepository::class);
    $mockWorkloadRepository->shouldReceive('get')->andReturn([]);

    $api = new HorizonApi(
        Mockery::mock(JobRepository::class),
        Mockery::mock(MetricsRepository::class),
        Mockery::mock(TagRepository::class),
        $mockWorkloadRepository,
        Mockery::mock(SupervisorRepository::class),
        Mockery::mock(MasterSupervisorRepository::class),
        Mockery::mock(BatchRepository::class),
    );

    $workload = $api->getWorkload();

    expect($workload)->toBeArray();
    expect($workload)->toBeEmpty();
});

it('can get masters', function () {
    $mockMasterSupervisorRepository = Mockery::mock(MasterSupervisorRepository::class);
    $mockMasterSupervisorRepository->shouldReceive('all')->andReturn([
        (object) ['id' => 'master-1', 'status' => 'running'],
        (object) ['id' => 'master-2', 'status' => 'paused'],
    ]);

    $api = new HorizonApi(
        Mockery::mock(JobRepository::class),
        Mockery::mock(MetricsRepository::class),
        Mockery::mock(TagRepository::class),
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        $mockMasterSupervisorRepository,
        Mockery::mock(BatchRepository::class),
    );

    $masters = $api->getMasters();

    expect($masters)->toBeArray();
    expect($masters)->toHaveCount(2);
});

it('can get empty masters', function () {
    $mockMasterSupervisorRepository = Mockery::mock(MasterSupervisorRepository::class);
    $mockMasterSupervisorRepository->shouldReceive('all')->andReturn([]);

    $api = new HorizonApi(
        Mockery::mock(JobRepository::class),
        Mockery::mock(MetricsRepository::class),
        Mockery::mock(TagRepository::class),
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        $mockMasterSupervisorRepository,
        Mockery::mock(BatchRepository::class),
    );

    $masters = $api->getMasters();

    expect($masters)->toBeArray();
    expect($masters)->toBeEmpty();
});

it('calculates total process count correctly', function () {
    $mockSupervisorRepository = Mockery::mock(SupervisorRepository::class);
    $mockSupervisorRepository->shouldReceive('all')->andReturn([
        (object) ['processes' => ['default' => 2, 'high' => 1]],
        (object) ['processes' => ['default' => 3]],
    ]);

    $api = new HorizonApi(
        Mockery::mock(JobRepository::class),
        Mockery::mock(MetricsRepository::class),
        Mockery::mock(TagRepository::class),
        Mockery::mock(WorkloadRepository::class),
        $mockSupervisorRepository,
        Mockery::mock(MasterSupervisorRepository::class),
        Mockery::mock(BatchRepository::class),
    );

    $reflection = new ReflectionClass($api);
    $method = $reflection->getMethod('totalProcessCount');
    $method->setAccessible(true);

    $count = $method->invoke($api);

    expect($count)->toBe(6);
});

it('calculates zero process count when no supervisors', function () {
    $mockSupervisorRepository = Mockery::mock(SupervisorRepository::class);
    $mockSupervisorRepository->shouldReceive('all')->andReturn([]);

    $api = new HorizonApi(
        Mockery::mock(JobRepository::class),
        Mockery::mock(MetricsRepository::class),
        Mockery::mock(TagRepository::class),
        Mockery::mock(WorkloadRepository::class),
        $mockSupervisorRepository,
        Mockery::mock(MasterSupervisorRepository::class),
        Mockery::mock(BatchRepository::class),
    );

    $reflection = new ReflectionClass($api);
    $method = $reflection->getMethod('totalProcessCount');
    $method->setAccessible(true);

    $count = $method->invoke($api);

    expect($count)->toBe(0);
});

it('returns inactive status when no masters', function () {
    $mockMasterSupervisorRepository = Mockery::mock(MasterSupervisorRepository::class);
    $mockMasterSupervisorRepository->shouldReceive('all')->andReturn([]);

    $api = new HorizonApi(
        Mockery::mock(JobRepository::class),
        Mockery::mock(MetricsRepository::class),
        Mockery::mock(TagRepository::class),
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        $mockMasterSupervisorRepository,
        Mockery::mock(BatchRepository::class),
    );

    $reflection = new ReflectionClass($api);
    $method = $reflection->getMethod('currentStatus');
    $method->setAccessible(true);

    $status = $method->invoke($api);

    expect($status)->toBe('inactive');
});

it('returns running status when masters are running', function () {
    $mockMasterSupervisorRepository = Mockery::mock(MasterSupervisorRepository::class);
    $mockMasterSupervisorRepository->shouldReceive('all')->andReturn([
        (object) ['status' => 'running'],
        (object) ['status' => 'running'],
    ]);

    $api = new HorizonApi(
        Mockery::mock(JobRepository::class),
        Mockery::mock(MetricsRepository::class),
        Mockery::mock(TagRepository::class),
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        $mockMasterSupervisorRepository,
        Mockery::mock(BatchRepository::class),
    );

    $reflection = new ReflectionClass($api);
    $method = $reflection->getMethod('currentStatus');
    $method->setAccessible(true);

    $status = $method->invoke($api);

    expect($status)->toBe('running');
});

it('returns paused status when all masters are paused', function () {
    $mockMasterSupervisorRepository = Mockery::mock(MasterSupervisorRepository::class);
    $mockMasterSupervisorRepository->shouldReceive('all')->andReturn([
        (object) ['status' => 'paused'],
        (object) ['status' => 'paused'],
    ]);

    $api = new HorizonApi(
        Mockery::mock(JobRepository::class),
        Mockery::mock(MetricsRepository::class),
        Mockery::mock(TagRepository::class),
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        $mockMasterSupervisorRepository,
        Mockery::mock(BatchRepository::class),
    );

    $reflection = new ReflectionClass($api);
    $method = $reflection->getMethod('currentStatus');
    $method->setAccessible(true);

    $status = $method->invoke($api);

    expect($status)->toBe('paused');
});

it('returns running status when some masters are running', function () {
    $mockMasterSupervisorRepository = Mockery::mock(MasterSupervisorRepository::class);
    $mockMasterSupervisorRepository->shouldReceive('all')->andReturn([
        (object) ['status' => 'running'],
        (object) ['status' => 'paused'],
    ]);

    $api = new HorizonApi(
        Mockery::mock(JobRepository::class),
        Mockery::mock(MetricsRepository::class),
        Mockery::mock(TagRepository::class),
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        $mockMasterSupervisorRepository,
        Mockery::mock(BatchRepository::class),
    );

    $reflection = new ReflectionClass($api);
    $method = $reflection->getMethod('currentStatus');
    $method->setAccessible(true);

    $status = $method->invoke($api);

    expect($status)->toBe('running');
});

it('calculates total paused masters correctly', function () {
    $mockMasterSupervisorRepository = Mockery::mock(MasterSupervisorRepository::class);
    $mockMasterSupervisorRepository->shouldReceive('all')->andReturn([
        (object) ['status' => 'paused'],
        (object) ['status' => 'running'],
        (object) ['status' => 'paused'],
    ]);

    $api = new HorizonApi(
        Mockery::mock(JobRepository::class),
        Mockery::mock(MetricsRepository::class),
        Mockery::mock(TagRepository::class),
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        $mockMasterSupervisorRepository,
        Mockery::mock(BatchRepository::class),
    );

    $reflection = new ReflectionClass($api);
    $method = $reflection->getMethod('totalPausedMasters');
    $method->setAccessible(true);

    $count = $method->invoke($api);

    expect($count)->toBe(2);
});

it('returns zero paused masters when none are paused', function () {
    $mockMasterSupervisorRepository = Mockery::mock(MasterSupervisorRepository::class);
    $mockMasterSupervisorRepository->shouldReceive('all')->andReturn([
        (object) ['status' => 'running'],
    ]);

    $api = new HorizonApi(
        Mockery::mock(JobRepository::class),
        Mockery::mock(MetricsRepository::class),
        Mockery::mock(TagRepository::class),
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        $mockMasterSupervisorRepository,
        Mockery::mock(BatchRepository::class),
    );

    $reflection = new ReflectionClass($api);
    $method = $reflection->getMethod('totalPausedMasters');
    $method->setAccessible(true);

    $count = $method->invoke($api);

    expect($count)->toBe(0);
});

it('returns zero paused masters when no masters exist', function () {
    $mockMasterSupervisorRepository = Mockery::mock(MasterSupervisorRepository::class);
    $mockMasterSupervisorRepository->shouldReceive('all')->andReturn([]);

    $api = new HorizonApi(
        Mockery::mock(JobRepository::class),
        Mockery::mock(MetricsRepository::class),
        Mockery::mock(TagRepository::class),
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        $mockMasterSupervisorRepository,
        Mockery::mock(BatchRepository::class),
    );

    $reflection = new ReflectionClass($api);
    $method = $reflection->getMethod('totalPausedMasters');
    $method->setAccessible(true);

    $count = $method->invoke($api);

    expect($count)->toBe(0);
});

it('can get pending jobs without tag', function () {
    $mockJobRepository = Mockery::mock(JobRepository::class);
    $job1 = (object) ['id' => '1', 'payload' => json_encode(['job' => 'test'])];
    $job2 = (object) ['id' => '2', 'payload' => json_encode(['job' => 'test2'])];

    $mockJobRepository->shouldReceive('getPending')->with(-1)->andReturn(collect([$job1, $job2]));
    $mockJobRepository->shouldReceive('countPending')->andReturn(2);

    $api = new HorizonApi(
        $mockJobRepository,
        Mockery::mock(MetricsRepository::class),
        Mockery::mock(TagRepository::class),
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        Mockery::mock(MasterSupervisorRepository::class),
        Mockery::mock(BatchRepository::class),
    );

    $result = $api->getPendingJobs();

    expect($result)->toBeArray();
    expect($result)->toHaveKeys(['jobs', 'total']);
    expect($result['jobs'])->toBeInstanceOf(Collection::class);
    expect($result['jobs'])->toHaveCount(2);
    expect($result['total'])->toBe(2);
    expect($result['jobs']->first()->payload)->toBeObject();
});

it('can get pending jobs with startingAt', function () {
    $mockJobRepository = Mockery::mock(JobRepository::class);
    $job1 = (object) ['id' => '1', 'payload' => json_encode(['job' => 'test'])];

    $mockJobRepository->shouldReceive('getPending')->with(50)->andReturn(collect([$job1]));
    $mockJobRepository->shouldReceive('countPending')->andReturn(51);

    $api = new HorizonApi(
        $mockJobRepository,
        Mockery::mock(MetricsRepository::class),
        Mockery::mock(TagRepository::class),
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        Mockery::mock(MasterSupervisorRepository::class),
        Mockery::mock(BatchRepository::class),
    );

    $result = $api->getPendingJobs(50);

    expect($result)->toBeArray();
    expect($result['total'])->toBe(51);
});

it('can get pending jobs with tag', function () {
    $mockJobRepository = Mockery::mock(JobRepository::class);
    $mockTagRepository = Mockery::mock(TagRepository::class);
    $job1 = (object) ['id' => '1', 'payload' => json_encode(['job' => 'test'])];

    $mockTagRepository->shouldReceive('paginate')->with('user:1', 0, 50)->andReturn(['1']);
    $mockJobRepository->shouldReceive('getJobs')->with(['1'], 0)->andReturn(collect([$job1]));
    $mockTagRepository->shouldReceive('count')->with('user:1')->andReturn(1);

    $api = new HorizonApi(
        $mockJobRepository,
        Mockery::mock(MetricsRepository::class),
        $mockTagRepository,
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        Mockery::mock(MasterSupervisorRepository::class),
        Mockery::mock(BatchRepository::class),
    );

    $result = $api->getPendingJobs(null, 'user:1');

    expect($result)->toBeArray();
    expect($result['total'])->toBe(1);
});

it('can get completed jobs without tag', function () {
    $mockJobRepository = Mockery::mock(JobRepository::class);
    $job1 = (object) ['id' => '1', 'payload' => json_encode(['job' => 'test'])];

    $mockJobRepository->shouldReceive('getCompleted')->with(-1)->andReturn(collect([$job1]));
    $mockJobRepository->shouldReceive('countCompleted')->andReturn(1);

    $api = new HorizonApi(
        $mockJobRepository,
        Mockery::mock(MetricsRepository::class),
        Mockery::mock(TagRepository::class),
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        Mockery::mock(MasterSupervisorRepository::class),
        Mockery::mock(BatchRepository::class),
    );

    $result = $api->getCompletedJobs();

    expect($result)->toBeArray();
    expect($result['total'])->toBe(1);
});

it('can get completed jobs with tag', function () {
    $mockJobRepository = Mockery::mock(JobRepository::class);
    $mockTagRepository = Mockery::mock(TagRepository::class);
    $job1 = (object) ['id' => '1', 'payload' => json_encode(['job' => 'test'])];

    $mockTagRepository->shouldReceive('paginate')->with('order:123', 0, 50)->andReturn(['1']);
    $mockJobRepository->shouldReceive('getJobs')->with(['1'], 0)->andReturn(collect([$job1]));
    $mockTagRepository->shouldReceive('count')->with('order:123')->andReturn(1);

    $api = new HorizonApi(
        $mockJobRepository,
        Mockery::mock(MetricsRepository::class),
        $mockTagRepository,
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        Mockery::mock(MasterSupervisorRepository::class),
        Mockery::mock(BatchRepository::class),
    );

    $result = $api->getCompletedJobs(null, 'order:123');

    expect($result)->toBeArray();
    expect($result['total'])->toBe(1);
});

it('can get silenced jobs without tag', function () {
    $mockJobRepository = Mockery::mock(JobRepository::class);
    $job1 = (object) ['id' => '1', 'payload' => json_encode(['job' => 'test'])];

    $mockJobRepository->shouldReceive('getSilenced')->with(-1)->andReturn(collect([$job1]));
    $mockJobRepository->shouldReceive('countSilenced')->andReturn(1);

    $api = new HorizonApi(
        $mockJobRepository,
        Mockery::mock(MetricsRepository::class),
        Mockery::mock(TagRepository::class),
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        Mockery::mock(MasterSupervisorRepository::class),
        Mockery::mock(BatchRepository::class),
    );

    $result = $api->getSilencedJobs();

    expect($result)->toBeArray();
    expect($result['total'])->toBe(1);
});

it('can get silenced jobs with tag', function () {
    $mockJobRepository = Mockery::mock(JobRepository::class);
    $mockTagRepository = Mockery::mock(TagRepository::class);
    $job1 = (object) ['id' => '1', 'payload' => json_encode(['job' => 'test'])];

    $mockTagRepository->shouldReceive('paginate')->with('tag:test', 0, 50)->andReturn(['1']);
    $mockJobRepository->shouldReceive('getJobs')->with(['1'], 0)->andReturn(collect([$job1]));
    $mockTagRepository->shouldReceive('count')->with('tag:test')->andReturn(1);

    $api = new HorizonApi(
        $mockJobRepository,
        Mockery::mock(MetricsRepository::class),
        $mockTagRepository,
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        Mockery::mock(MasterSupervisorRepository::class),
        Mockery::mock(BatchRepository::class),
    );

    $result = $api->getSilencedJobs(null, 'tag:test');

    expect($result)->toBeArray();
    expect($result['total'])->toBe(1);
});

it('can get failed jobs without tag', function () {
    $mockJobRepository = Mockery::mock(JobRepository::class);
    $job1 = (object) [
        'id' => '1',
        'payload' => json_encode(['job' => 'test']),
        'exception' => 'Error',
        'context' => json_encode(['key' => 'value']),
        'retried_by' => null,
    ];

    $mockJobRepository->shouldReceive('getFailed')->with(-1)->andReturn(collect([$job1]));
    $mockJobRepository->shouldReceive('countFailed')->andReturn(1);

    $api = new HorizonApi(
        $mockJobRepository,
        Mockery::mock(MetricsRepository::class),
        Mockery::mock(TagRepository::class),
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        Mockery::mock(MasterSupervisorRepository::class),
        Mockery::mock(BatchRepository::class),
    );

    $result = $api->getFailedJobs();

    expect($result)->toBeArray();
    expect($result['total'])->toBe(1);
    expect($result['jobs']->first()->payload)->toBeObject();
    expect($result['jobs']->first()->exception)->toBeString();
});

it('can get failed jobs with tag', function () {
    $mockJobRepository = Mockery::mock(JobRepository::class);
    $mockTagRepository = Mockery::mock(TagRepository::class);
    $job1 = (object) [
        'id' => '1',
        'payload' => json_encode(['job' => 'test']),
        'exception' => 'Error',
        'context' => json_encode(['key' => 'value']),
        'retried_by' => null,
    ];

    $mockTagRepository->shouldReceive('paginate')->with('failed:user:1', 0, 50)->andReturn(['1']);
    $mockJobRepository->shouldReceive('getJobs')->with(['1'], 0)->andReturn(collect([$job1]));
    $mockTagRepository->shouldReceive('count')->with('failed:user:1')->andReturn(1);

    $api = new HorizonApi(
        $mockJobRepository,
        Mockery::mock(MetricsRepository::class),
        $mockTagRepository,
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        Mockery::mock(MasterSupervisorRepository::class),
        Mockery::mock(BatchRepository::class),
    );

    $result = $api->getFailedJobs(null, 'user:1');

    expect($result)->toBeArray();
    expect($result['total'])->toBe(1);
});

it('can get job by id', function () {
    $mockJobRepository = Mockery::mock(JobRepository::class);
    $job = (object) ['id' => '1', 'payload' => json_encode(['job' => 'test'])];

    $mockJobRepository->shouldReceive('getJobs')->with(['1'])->andReturn(collect([$job]));

    $api = new HorizonApi(
        $mockJobRepository,
        Mockery::mock(MetricsRepository::class),
        Mockery::mock(TagRepository::class),
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        Mockery::mock(MasterSupervisorRepository::class),
        Mockery::mock(BatchRepository::class),
    );

    $result = $api->getJob('1');

    expect($result)->toBeObject();
    expect($result->payload)->toBeObject();
});

it('returns null when job does not exist', function () {
    $mockJobRepository = Mockery::mock(JobRepository::class);

    $mockJobRepository->shouldReceive('getJobs')->with(['1'])->andReturn(collect([]));

    $api = new HorizonApi(
        $mockJobRepository,
        Mockery::mock(MetricsRepository::class),
        Mockery::mock(TagRepository::class),
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        Mockery::mock(MasterSupervisorRepository::class),
        Mockery::mock(BatchRepository::class),
    );

    $result = $api->getJob('1');

    expect($result)->toBeNull();
});

it('can get failed job by id', function () {
    $mockJobRepository = Mockery::mock(JobRepository::class);
    $job = (object) [
        'id' => '1',
        'payload' => json_encode(['job' => 'test']),
        'exception' => 'Error',
        'context' => json_encode(['key' => 'value']),
        'retried_by' => null,
    ];

    $mockJobRepository->shouldReceive('getJobs')->with(['1'])->andReturn(collect([$job]));

    $api = new HorizonApi(
        $mockJobRepository,
        Mockery::mock(MetricsRepository::class),
        Mockery::mock(TagRepository::class),
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        Mockery::mock(MasterSupervisorRepository::class),
        Mockery::mock(BatchRepository::class),
    );

    $result = $api->getFailedJob('1');

    expect($result)->toBeObject();
    expect($result->payload)->toBeObject();
    expect($result->exception)->toBeString();
});

it('returns null when failed job does not exist', function () {
    $mockJobRepository = Mockery::mock(JobRepository::class);

    $mockJobRepository->shouldReceive('getJobs')->with(['1'])->andReturn(collect([]));

    $api = new HorizonApi(
        $mockJobRepository,
        Mockery::mock(MetricsRepository::class),
        Mockery::mock(TagRepository::class),
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        Mockery::mock(MasterSupervisorRepository::class),
        Mockery::mock(BatchRepository::class),
    );

    $result = $api->getFailedJob('1');

    expect($result)->toBeNull();
});

it('can retry job', function () {
    Bus::fake();

    $api = createMockApi();

    $api->retryJob('job-1');

    Bus::assertDispatched(RetryFailedJob::class, function ($job) {
        return $job->id === 'job-1';
    });
});

it('decodes job payload correctly', function () {
    $mockJobRepository = Mockery::mock(JobRepository::class);
    $job = (object) ['id' => '1', 'payload' => json_encode(['job' => 'test', 'data' => ['key' => 'value']])];

    $mockJobRepository->shouldReceive('getJobs')->with(['1'])->andReturn(collect([$job]));

    $api = new HorizonApi(
        $mockJobRepository,
        Mockery::mock(MetricsRepository::class),
        Mockery::mock(TagRepository::class),
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        Mockery::mock(MasterSupervisorRepository::class),
        Mockery::mock(BatchRepository::class),
    );

    $result = $api->getJob('1');

    expect($result->payload)->toBeObject();
    expect($result->payload->job)->toBe('test');
});

it('decodes failed job correctly', function () {
    $mockJobRepository = Mockery::mock(JobRepository::class);
    $retriedBy = json_encode([
        ['retried_at' => '2024-01-01 10:00:00', 'status' => 'completed'],
        ['retried_at' => '2024-01-01 09:00:00', 'status' => 'failed'],
    ]);
    $job = (object) [
        'id' => '1',
        'payload' => json_encode(['job' => 'test']),
        'exception' => 'Error message',
        'context' => json_encode(['key' => 'value']),
        'retried_by' => $retriedBy,
    ];

    $mockJobRepository->shouldReceive('getJobs')->with(['1'])->andReturn(collect([$job]));

    $api = new HorizonApi(
        $mockJobRepository,
        Mockery::mock(MetricsRepository::class),
        Mockery::mock(TagRepository::class),
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        Mockery::mock(MasterSupervisorRepository::class),
        Mockery::mock(BatchRepository::class),
    );

    $result = $api->getFailedJob('1');

    expect($result->payload)->toBeObject();
    expect($result->exception)->toBeString();
    expect($result->context)->toBeObject();
    expect($result->retried_by)->toBeInstanceOf(Collection::class);
    expect($result->retried_by->first()->status)->toBe('completed'); // Should be sorted by retried_at desc
});

it('handles null retried_by in failed job', function () {
    $mockJobRepository = Mockery::mock(JobRepository::class);
    $job = (object) [
        'id' => '1',
        'payload' => json_encode(['job' => 'test']),
        'exception' => 'Error',
        'context' => json_encode([]),
        'retried_by' => null,
    ];

    $mockJobRepository->shouldReceive('getJobs')->with(['1'])->andReturn(collect([$job]));

    $api = new HorizonApi(
        $mockJobRepository,
        Mockery::mock(MetricsRepository::class),
        Mockery::mock(TagRepository::class),
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        Mockery::mock(MasterSupervisorRepository::class),
        Mockery::mock(BatchRepository::class),
    );

    $result = $api->getFailedJob('1');

    expect($result->retried_by)->toBeInstanceOf(Collection::class);
    expect($result->retried_by)->toBeEmpty();
});

it('handles empty context in failed job', function () {
    $mockJobRepository = Mockery::mock(JobRepository::class);
    $job = (object) [
        'id' => '1',
        'payload' => json_encode(['job' => 'test']),
        'exception' => 'Error',
        'context' => '',
        'retried_by' => null,
    ];

    $mockJobRepository->shouldReceive('getJobs')->with(['1'])->andReturn(collect([$job]));

    $api = new HorizonApi(
        $mockJobRepository,
        Mockery::mock(MetricsRepository::class),
        Mockery::mock(TagRepository::class),
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        Mockery::mock(MasterSupervisorRepository::class),
        Mockery::mock(BatchRepository::class),
    );

    $result = $api->getFailedJob('1');

    expect($result->context)->toBeNull();
});

it('can get monitored tags', function () {
    $mockTagRepository = Mockery::mock(TagRepository::class);
    $mockTagRepository->shouldReceive('monitoring')->andReturn(['user:1', 'order:123']);
    $mockTagRepository->shouldReceive('count')->with('user:1')->andReturn(5);
    $mockTagRepository->shouldReceive('count')->with('failed:user:1')->andReturn(1);
    $mockTagRepository->shouldReceive('count')->with('order:123')->andReturn(10);
    $mockTagRepository->shouldReceive('count')->with('failed:order:123')->andReturn(0);

    $api = new HorizonApi(
        Mockery::mock(JobRepository::class),
        Mockery::mock(MetricsRepository::class),
        $mockTagRepository,
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        Mockery::mock(MasterSupervisorRepository::class),
        Mockery::mock(BatchRepository::class),
    );

    $tags = $api->getMonitoredTags();

    expect($tags)->toBeInstanceOf(Collection::class);
    expect($tags)->toHaveCount(2);
    expect($tags->first()['tag'])->toBe('order:123'); // Should be sorted
    expect($tags->first()['count'])->toBe(10);
});

it('can get empty monitored tags', function () {
    $mockTagRepository = Mockery::mock(TagRepository::class);
    $mockTagRepository->shouldReceive('monitoring')->andReturn([]);

    $api = new HorizonApi(
        Mockery::mock(JobRepository::class),
        Mockery::mock(MetricsRepository::class),
        $mockTagRepository,
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        Mockery::mock(MasterSupervisorRepository::class),
        Mockery::mock(BatchRepository::class),
    );

    $tags = $api->getMonitoredTags();

    expect($tags)->toBeInstanceOf(Collection::class);
    expect($tags)->toBeEmpty();
});

it('can start monitoring tag', function () {
    Bus::fake();

    $api = createMockApi();

    $api->startMonitoring('user:1');

    Bus::assertDispatched(MonitorTag::class, function ($job) {
        return $job->tag === 'user:1';
    });
});

it('can stop monitoring tag', function () {
    Bus::fake();

    $api = createMockApi();

    $api->stopMonitoring('user:1');

    Bus::assertDispatched(StopMonitoringTag::class, function ($job) {
        return $job->tag === 'user:1';
    });
});

it('can get tag jobs', function () {
    $mockJobRepository = Mockery::mock(JobRepository::class);
    $mockTagRepository = Mockery::mock(TagRepository::class);
    $job1 = (object) ['id' => '1', 'payload' => json_encode(['job' => 'test'])];

    $mockTagRepository->shouldReceive('paginate')->with('user:1', 0, 25)->andReturn(['1']);
    $mockJobRepository->shouldReceive('getJobs')->with(['1'], 0)->andReturn(collect([$job1]));
    $mockTagRepository->shouldReceive('count')->with('user:1')->andReturn(1);

    $api = new HorizonApi(
        $mockJobRepository,
        Mockery::mock(MetricsRepository::class),
        $mockTagRepository,
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        Mockery::mock(MasterSupervisorRepository::class),
        Mockery::mock(BatchRepository::class),
    );

    $result = $api->getTagJobs('user:1');

    expect($result)->toBeArray();
    expect($result['total'])->toBe(1);
    expect($result['jobs'])->toBeInstanceOf(Collection::class);
});

it('can get tag jobs with pagination', function () {
    $mockJobRepository = Mockery::mock(JobRepository::class);
    $mockTagRepository = Mockery::mock(TagRepository::class);
    $job1 = (object) ['id' => '1', 'payload' => json_encode(['job' => 'test'])];

    $mockTagRepository->shouldReceive('paginate')->with('user:1', 25, 25)->andReturn(['1']);
    $mockJobRepository->shouldReceive('getJobs')->with(['1'], 25)->andReturn(collect([$job1]));
    $mockTagRepository->shouldReceive('count')->with('user:1')->andReturn(50);

    $api = new HorizonApi(
        $mockJobRepository,
        Mockery::mock(MetricsRepository::class),
        $mockTagRepository,
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        Mockery::mock(MasterSupervisorRepository::class),
        Mockery::mock(BatchRepository::class),
    );

    $result = $api->getTagJobs('user:1', 25, 25);

    expect($result)->toBeArray();
    expect($result['total'])->toBe(50);
});

it('can get tag failed jobs', function () {
    $mockJobRepository = Mockery::mock(JobRepository::class);
    $mockTagRepository = Mockery::mock(TagRepository::class);
    $job1 = (object) [
        'id' => '1',
        'payload' => json_encode(['job' => 'test']),
        'exception' => 'Error',
        'context' => json_encode([]),
        'retried_by' => null,
    ];

    $mockTagRepository->shouldReceive('paginate')->with('failed:user:1', 0, 25)->andReturn(['1']);
    $mockJobRepository->shouldReceive('getJobs')->with(['1'], 0)->andReturn(collect([$job1]));
    $mockTagRepository->shouldReceive('count')->with('failed:user:1')->andReturn(1);

    $api = new HorizonApi(
        $mockJobRepository,
        Mockery::mock(MetricsRepository::class),
        $mockTagRepository,
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        Mockery::mock(MasterSupervisorRepository::class),
        Mockery::mock(BatchRepository::class),
    );

    $result = $api->getTagFailedJobs('user:1');

    expect($result)->toBeArray();
    expect($result['total'])->toBe(1);
});

it('can get measured jobs', function () {
    $mockMetricsRepository = Mockery::mock(MetricsRepository::class);
    $mockMetricsRepository->shouldReceive('measuredJobs')->andReturn(['App\Jobs\TestJob', 'App\Jobs\AnotherJob']);
    $mockMetricsRepository->shouldReceive('throughputForJob')->with('App\Jobs\TestJob')->andReturn(100);
    $mockMetricsRepository->shouldReceive('runtimeForJob')->with('App\Jobs\TestJob')->andReturn(50.5);
    $mockMetricsRepository->shouldReceive('throughputForJob')->with('App\Jobs\AnotherJob')->andReturn(200);
    $mockMetricsRepository->shouldReceive('runtimeForJob')->with('App\Jobs\AnotherJob')->andReturn(75.2);

    $api = new HorizonApi(
        Mockery::mock(JobRepository::class),
        $mockMetricsRepository,
        Mockery::mock(TagRepository::class),
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        Mockery::mock(MasterSupervisorRepository::class),
        Mockery::mock(BatchRepository::class),
    );

    $result = $api->getMeasuredJobs();

    expect($result)->toBeArray();
    expect($result)->toHaveCount(2);
    expect($result[0]['name'])->toBe('App\Jobs\AnotherJob'); // Should be sorted by throughput desc
    expect($result[0]['throughput'])->toBe(200);
});

it('can get measured queues', function () {
    $mockMetricsRepository = Mockery::mock(MetricsRepository::class);
    $mockMetricsRepository->shouldReceive('measuredQueues')->andReturn(['default', 'high']);
    $mockMetricsRepository->shouldReceive('throughputForQueue')->with('default')->andReturn(150);
    $mockMetricsRepository->shouldReceive('runtimeForQueue')->with('default')->andReturn(60.0);
    $mockMetricsRepository->shouldReceive('throughputForQueue')->with('high')->andReturn(300);
    $mockMetricsRepository->shouldReceive('runtimeForQueue')->with('high')->andReturn(80.5);

    $api = new HorizonApi(
        Mockery::mock(JobRepository::class),
        $mockMetricsRepository,
        Mockery::mock(TagRepository::class),
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        Mockery::mock(MasterSupervisorRepository::class),
        Mockery::mock(BatchRepository::class),
    );

    $result = $api->getMeasuredQueues();

    expect($result)->toBeArray();
    expect($result)->toHaveCount(2);
    expect($result[0]['name'])->toBe('high'); // Should be sorted by throughput desc
    expect($result[0]['throughput'])->toBe(300);
});

it('can get job snapshots', function () {
    $mockMetricsRepository = Mockery::mock(MetricsRepository::class);
    $mockMetricsRepository->shouldReceive('snapshotsForJob')->with('App\Jobs\TestJob')->andReturn([
        (object) ['time' => 1000, 'throughput' => 10, 'runtime' => 50],
    ]);

    $api = new HorizonApi(
        Mockery::mock(JobRepository::class),
        $mockMetricsRepository,
        Mockery::mock(TagRepository::class),
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        Mockery::mock(MasterSupervisorRepository::class),
        Mockery::mock(BatchRepository::class),
    );

    $result = $api->getJobSnapshots('App\Jobs\TestJob');

    expect($result)->toBeArray();
    expect($result)->toHaveCount(1);
});

it('can get queue snapshots', function () {
    $mockMetricsRepository = Mockery::mock(MetricsRepository::class);
    $mockMetricsRepository->shouldReceive('snapshotsForQueue')->with('default')->andReturn([
        (object) ['time' => 1000, 'throughput' => 20, 'runtime' => 60],
    ]);

    $api = new HorizonApi(
        Mockery::mock(JobRepository::class),
        $mockMetricsRepository,
        Mockery::mock(TagRepository::class),
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        Mockery::mock(MasterSupervisorRepository::class),
        Mockery::mock(BatchRepository::class),
    );

    $result = $api->getQueueSnapshots('default');

    expect($result)->toBeArray();
    expect($result)->toHaveCount(1);
});

it('can get batches', function () {
    $mockBatchRepository = Mockery::mock(BatchRepository::class);
    $batch1 = (object) ['id' => 'batch-1', 'name' => 'Test Batch'];
    $batch2 = (object) ['id' => 'batch-2', 'name' => 'Another Batch'];

    $mockBatchRepository->shouldReceive('get')->with(50, null)->andReturn([$batch1, $batch2]);

    $api = new HorizonApi(
        Mockery::mock(JobRepository::class),
        Mockery::mock(MetricsRepository::class),
        Mockery::mock(TagRepository::class),
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        Mockery::mock(MasterSupervisorRepository::class),
        $mockBatchRepository,
    );

    $result = $api->getBatches();

    expect($result)->toBeArray();
    expect($result)->toHaveKey('batches');
    expect($result['batches'])->toHaveCount(2);
});

it('can get batches with beforeId', function () {
    $mockBatchRepository = Mockery::mock(BatchRepository::class);
    $batch1 = (object) ['id' => 'batch-1', 'name' => 'Test Batch'];

    $mockBatchRepository->shouldReceive('get')->with(50, 'batch-2')->andReturn([$batch1]);

    $api = new HorizonApi(
        Mockery::mock(JobRepository::class),
        Mockery::mock(MetricsRepository::class),
        Mockery::mock(TagRepository::class),
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        Mockery::mock(MasterSupervisorRepository::class),
        $mockBatchRepository,
    );

    $result = $api->getBatches('batch-2');

    expect($result)->toBeArray();
    expect($result['batches'])->toHaveCount(1);
});


it('can get batch by id', function () {
    $mockBatchRepository = Mockery::mock(BatchRepository::class);
    $mockJobRepository = Mockery::mock(JobRepository::class);
    $batch = (object) ['id' => 'batch-1', 'failedJobIds' => ['1', '2']];
    $job1 = (object) ['id' => '1', 'payload' => json_encode(['job' => 'test'])];

    $mockBatchRepository->shouldReceive('find')->with('batch-1')->andReturn($batch);
    $mockJobRepository->shouldReceive('getJobs')->with(['1', '2'])->andReturn(collect([$job1]));

    $api = new HorizonApi(
        $mockJobRepository,
        Mockery::mock(MetricsRepository::class),
        Mockery::mock(TagRepository::class),
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        Mockery::mock(MasterSupervisorRepository::class),
        $mockBatchRepository,
    );

    $result = $api->getBatch('batch-1');

    expect($result)->toBeArray();
    expect($result)->toHaveKeys(['batch', 'failedJobs']);
    expect($result['batch'])->toBe($batch);
    expect($result['failedJobs'])->toBeInstanceOf(Collection::class);
});

it('returns null failedJobs when batch does not exist', function () {
    $mockBatchRepository = Mockery::mock(BatchRepository::class);
    $mockBatchRepository->shouldReceive('find')->with('batch-1')->andReturn(null);

    $api = new HorizonApi(
        Mockery::mock(JobRepository::class),
        Mockery::mock(MetricsRepository::class),
        Mockery::mock(TagRepository::class),
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        Mockery::mock(MasterSupervisorRepository::class),
        $mockBatchRepository,
    );

    $result = $api->getBatch('batch-1');

    expect($result)->toBeArray();
    expect($result['batch'])->toBeNull();
    expect($result['failedJobs'])->toBeNull();
});

it('can retry batch', function () {
    Bus::fake();

    $mockBatchRepository = Mockery::mock(BatchRepository::class);
    $mockJobRepository = Mockery::mock(JobRepository::class);
    $batch = (object) ['id' => 'batch-1', 'failedJobIds' => ['1', '2']];
    $job1 = (object) ['id' => '1', 'payload' => json_encode(['job' => 'test', 'retry_of' => null])];
    $job2 = (object) ['id' => '2', 'payload' => json_encode(['job' => 'test2', 'retry_of' => '1'])];

    $mockBatchRepository->shouldReceive('find')->with('batch-1')->andReturn($batch);
    $mockJobRepository->shouldReceive('getJobs')->with(['1', '2'])->andReturn(collect([$job1, $job2]));

    $api = new HorizonApi(
        $mockJobRepository,
        Mockery::mock(MetricsRepository::class),
        Mockery::mock(TagRepository::class),
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        Mockery::mock(MasterSupervisorRepository::class),
        $mockBatchRepository,
    );

    $api->retryBatch('batch-1');

    Bus::assertDispatched(RetryFailedJob::class, function ($job) {
        return $job->id === '1';
    });

    Bus::assertNotDispatched(RetryFailedJob::class, function ($job) {
        return $job->id === '2';
    });
});

it('does not retry batch when batch does not exist', function () {
    Bus::fake();

    $mockBatchRepository = Mockery::mock(BatchRepository::class);
    $mockBatchRepository->shouldReceive('find')->with('batch-1')->andReturn(null);

    $api = new HorizonApi(
        Mockery::mock(JobRepository::class),
        Mockery::mock(MetricsRepository::class),
        Mockery::mock(TagRepository::class),
        Mockery::mock(WorkloadRepository::class),
        Mockery::mock(SupervisorRepository::class),
        Mockery::mock(MasterSupervisorRepository::class),
        $mockBatchRepository,
    );

    $api->retryBatch('batch-1');

    Bus::assertNothingDispatched();
});

afterEach(function () {
    Mockery::close();
});
