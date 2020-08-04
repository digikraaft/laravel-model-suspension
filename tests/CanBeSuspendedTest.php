<?php


namespace Digikraaft\ModelSuspension\Tests;

use Digikraaft\ModelSuspension\Exceptions\InvalidDate;
use Digikraaft\ModelSuspension\Tests\Models\AlternativeSuspensionModel;
use Digikraaft\ModelSuspension\Tests\Models\CustomModelKeySuspensionModel;
use Digikraaft\ModelSuspension\Tests\Models\TestModel;
use Spatie\TestTime\TestTime;

class CanBeSuspendedTest extends TestCase
{
    /** @var TestModel */
    protected TestModel $testModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testModel = TestModel::create([
            'name' => 'name',
        ]);
    }

    /** @test */
    public function it_can_suspend_model()
    {
        $this->testModel->suspend(1, 'privacy violation');
        $reason = $this->testModel->suspension()->reason;

        $this->assertEquals('privacy violation', $reason);
        $this->assertTrue($this->testModel->isSuspended());
    }

    /** @test */
    public function it_can_suspend_model_without_suspension_days()
    {
        $this->testModel->suspend();
        $this->assertTrue($this->testModel->isSuspended());
    }

    /** @test */
    public function it_can_handle_an_empty_reason_when_suspending()
    {
        $this->testModel->suspend(1);

        $this->assertTrue($this->testModel->isSuspended());
    }

    /** @test */
    public function a_reason_can_be_set()
    {
        $this->testModel->suspend(1, 'privacy violation');

        $this->assertEquals('privacy violation', $this->testModel->suspension()->reason);
    }

    /** @test */
    public function it_can_handle_getting_a_suspension_when_there_are_none_set()
    {
        $this->assertNull($this->testModel->suspension());
    }
    /** @test */
    public function it_allows_null_for_an_empty_reason_when_suspending()
    {
        $this->testModel->suspend(1, null);

        $this->assertNull($this->testModel->suspension()->reason);
    }

    /** @test */
    public function it_allows_for_empty_days_and_reason_when_suspending()
    {
        $this->testModel->suspend();
        $this->assertTrue($this->testModel->isSuspended());
    }

    /** @test */
    public function it_can_return_the_latest_suspension()
    {
        $this->testModel->suspend();
        $this->testModel->suspend(1, 'second suspension');
        $this->testModel->suspend(1, 'last suspension');

        $this->assertEquals(
            'last suspension',
            $this->testModel->suspension()->reason
        );
        $this->assertNotEquals('second suspension', $this->testModel->suspension()->reason);
    }

    /** @test */
    public function it_can_return_the_latest_suspension_after_unsuspending()
    {
        $this->testModel->suspend()
                        ->suspend(1, 'second suspension')
                        ->unsuspend()
                        ->suspend(null, 'last suspension');

        $this->assertEquals(
            'last suspension',
            $this->testModel->suspension()->reason
        );
        $this->assertNotEquals('second suspension', $this->testModel->suspension()->reason);
    }

    /** @test */
    public function it_becomes_unsuspended_after_suspension_period()
    {
        TestTime::freeze('Y-m-d', '2020-07-23');
        TestTime::subDays(10);
        $this->testModel->suspensions()->create([
            'is_suspended' => true,
            'suspended_until' => now(),
            'reason' => null,
        ]);
        TestTime::freeze('Y-m-d', '2020-07-23');
        $this->assertFalse($this->testModel->suspension()->suspended_until >= now()->toDateString());
    }

    /** @test */
    public function it_still_suspends_when_suspension_date_is_in_the_future_and_is_suspended_is_true()
    {
        $this->testModel->suspend(10);
        $this->assertTrue($this->testModel->isSuspended());
    }

    /** @test */
    public function it_can_unsuspend()
    {
        $this->testModel->suspend(10);
        $this->testModel->unsuspend();
        $this->assertFalse($this->testModel->isSuspended());
    }

    /** @test */
    public function it_can_use_a_different_suspension_model()
    {
        $this->app['config']->set(
            'model-suspension.suspension_model',
            AlternativeSuspensionModel::class
        );

        $this->testModel->suspend(1, 'privacy violation');

        $this->assertInstanceOf(AlternativeSuspensionModel::class, $this->testModel->suspension());
    }

    /** @test */
    public function it_can_use_a_custom_name_for_the_relationship_id_column()
    {
        $this->app['config']->set(
            'model-suspension.suspension_model',
            CustomModelKeySuspensionModel::class
        );

        $this->app['config']->set(
            'model-suspension.model_primary_key_attribute',
            'model_custom_fk'
        );

        $model = TestModel::create([
            'name' => 'Tim O',
        ]);
        $model->suspend();

        $this->assertEquals($model->id, $model->suspension()->model_custom_fk);
        $this->assertTrue($model->suspension()->is(CustomModelKeySuspensionModel::first()));
    }

    /** @test */
    public function it_uses_the_default_relationship_id_column_when_configuration_value_is_no_present()
    {
        $this->app['config']->offsetUnset('model-suspension.model_primary_key_attribute');

        $model = TestModel::create(['name' => 'Tim O']);
        $model->suspend(1, 'privacy violation');

        $this->assertEquals('privacy violation', $model->suspension()->reason);
        $this->assertEquals($model->id, $model->suspension()->model_id);
    }

    /** @test */
    public function it_can_assert_that_user_has_been_suspended_before()
    {
        $this->testModel->suspend(1);
        $this->assertTrue($this->testModel->isSuspended());

        $this->testModel->unsuspend();
        $this->assertFalse($this->testModel->isSuspended());
        $this->assertTrue($this->testModel->hasEverBeenSuspended());
    }

    /** @test */
    public function it_can_return_number_of_times_suspended()
    {
        $this->testModel->suspend(1);
        $this->assertTrue($this->testModel->isSuspended());

        $this->testModel->unsuspend();
        $this->assertFalse($this->testModel->isSuspended());

        $this->testModel->suspend(5);

        $this->assertEquals(2, $this->testModel->numberOfTimesSuspended());
    }

    /** @test */
    public function it_throws_error_when_from_date_is_later_than_to_date()
    {
        $this->testModel->suspend(1);
        $this->assertTrue($this->testModel->isSuspended());

        $this->testModel->unsuspend();
        $this->assertFalse($this->testModel->isSuspended());

        $this->testModel->suspend(5);

        $this->expectException(InvalidDate::class);
        $this->testModel->numberOfTimesSuspended(now(), now()->subDays(2));
    }

    /** @test */
    public function it_can_return_number_of_times_suspended_when_period_is_specified()
    {
        $this->testModel->suspensions()->create([
            'is_suspended' => true,
            'reason' => null,
            'created_at' => now()->subMonths(2),
        ]);
        $this->assertTrue($this->testModel->isSuspended());

        $this->testModel->suspensions()->create([
            'is_suspended' => true,
            'suspended_until' => now(),
            'reason' => null,
            'created_at' => now()->subMonth(),
        ]);
        $this->assertTrue($this->testModel->isSuspended());

        $this->assertEquals(
            2,
            $this->testModel->numberOfTimesSuspended(now()->subMonths(2), now()->subMonth())
        );
    }

    /** @test */
    public function it_can_get_scoped_suspensions()
    {
        $model = TestModel::create(['name' => 'Tim O']);
        $model->suspend(1, 'privacy violation');
        $this->assertEquals('privacy violation', $model->suspension()->reason);

        $model = TestModel::create(['name' => 'Digikraaft']);
        $model->suspend(1, 'repeated payment failure');
        $this->assertEquals('repeated payment failure', $model->suspension()->reason);
        $model->unsuspend();
        $this->assertFalse($model->isSuspended());

        $model = TestModel::create(['name' => 'Digikraaft NG']);
        $model->suspend(1, 'audit investigation');
        $this->assertEquals('audit investigation', $model->suspension()->reason);

        $this->assertEquals(3, TestModel::allSuspensions()->count());
    }

    /** @test */
    public function it_can_get_active_scoped_suspensions()
    {
        $model = TestModel::create(['name' => 'Tim O']);
        $model->suspend(1, 'privacy violation');
        $this->assertEquals('privacy violation', $model->suspension()->reason);

        $model = TestModel::create(['name' => 'Digikraaft']);
        $model->suspend(1, 'repeated payment failure');
        $this->assertEquals('repeated payment failure', $model->suspension()->reason);
        $model->unsuspend();
        $this->assertFalse($model->isSuspended());

        $model = TestModel::create(['name' => 'Digikraaft NG']);
        $model->suspend(4, 'audit investigation');
        $this->assertEquals('audit investigation', $model->suspension()->reason);
        $this->assertTrue($model->isSuspended());

        $this->assertEquals(2, TestModel::activeSuspensions()->count());
        $model->unsuspend();
        $this->assertEquals(1, TestModel::activeSuspensions()->count());
    }

    /** @test */
    public function it_can_get_non_active_scoped_suspensions()
    {
        $model = TestModel::create(['name' => 'Tim O']);
        $model->suspend(1, 'privacy violation');
        $this->assertEquals('privacy violation', $model->suspension()->reason);

        $model = TestModel::create(['name' => 'Digikraaft']);
        $model->suspend(1, 'repeated payment failure');
        $this->assertEquals('repeated payment failure', $model->suspension()->reason);
        $model->unsuspend();
        $this->assertFalse($model->isSuspended());

        $model = TestModel::create(['name' => 'Digikraaft NG']);
        $model->suspend(4, 'audit investigation');
        $this->assertEquals('audit investigation', $model->suspension()->reason);
        $this->assertTrue($model->isSuspended());

        $this->assertEquals(1, TestModel::noneActiveSuspensions()->count());
        $model->unsuspend();
        $this->assertEquals(2, TestModel::noneActiveSuspensions()->count());
    }

    /** @test */
    public function it_can_check_if_model_is_suspended_when_it_is_not()
    {
        $this->assertFalse($this->testModel->isSuspended());
    }
}
