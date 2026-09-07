<?php

namespace Tests\Unit;

use App\Models\EnergyTracker\MeasurementDevice;
use App\Models\EnergyTracker\MeasurementDeviceContract;
use App\Models\Financial\FixedCost;
use App\Models\Financial\Insurance;
use App\Models\Inventory\Article;
use App\Models\Inventory\Collection;
use App\Models\Inventory\Location;
use App\Models\User;
use App\Services\Reminder\ReminderTargetRegistry;
use Tests\TestCase;

class ReminderTargetRegistryTest extends TestCase
{
    private ReminderTargetRegistry $registry;

    public function test_it_registers_all_four_target_models(): void
    {
        $classes = array_map(fn($target) => $target->modelClass, $this->registry->all());

        $this->assertContains(FixedCost::class, $classes);
        $this->assertContains(Insurance::class, $classes);
        $this->assertContains(Article::class, $classes);
        $this->assertContains(MeasurementDeviceContract::class, $classes);
        $this->assertCount(4, $classes);
    }

    public function test_date_properties_use_real_model_constants(): void
    {
        $fixedCost = $this->registry->datePropertyOptions(FixedCost::class);
        $this->assertArrayHasKey(FixedCost::next_booking_date, $fixedCost);
        $this->assertArrayHasKey(FixedCost::extended_date, $fixedCost);
        $this->assertArrayHasKey(FixedCost::ends_date, $fixedCost);

        $insurance = $this->registry->datePropertyOptions(Insurance::class);
        $this->assertArrayHasKey(Insurance::start_date, $insurance);
        $this->assertArrayHasKey(Insurance::end_date, $insurance);

        $article = $this->registry->datePropertyOptions(Article::class);
        $this->assertArrayHasKey(Article::purchase_date, $article);
        $this->assertArrayHasKey(Article::warranty_until, $article);
        $this->assertArrayHasKey(Article::sold_at, $article);

        $contract = $this->registry->datePropertyOptions(MeasurementDeviceContract::class);
        $this->assertArrayHasKey(MeasurementDeviceContract::starts_on, $contract);
        $this->assertArrayHasKey(MeasurementDeviceContract::ends_on, $contract);
    }

    public function test_it_returns_null_for_an_unknown_model(): void
    {
        $this->assertNull($this->registry->forModel(User::class));
        $this->assertSame([], $this->registry->datePropertyOptions(null));
    }

    public function test_it_resolves_the_user_for_fixed_cost_via_direct_relation(): void
    {
        $user = new User();
        $user->forceFill([User::name => 'Max']);

        $fixedCost = new FixedCost();
        $fixedCost->setRelation('user', $user);

        $resolved = $this->registry->forModel(FixedCost::class)?->resolveUser($fixedCost);

        $this->assertSame($user, $resolved);
    }

    public function test_it_resolves_the_user_for_article_via_the_location_collection_chain(): void
    {
        $user = new User();
        $user->forceFill([User::name => 'Max']);

        $collection = new Collection();
        $collection->setRelation('user', $user);

        $location = new Location();
        $location->setRelation(Location::belongs_to_collection, $collection);

        $article = new Article();
        $article->setRelation(Article::belongs_to_location, $location);

        $resolved = $this->registry->forModel(Article::class)?->resolveUser($article);

        $this->assertSame($user, $resolved);
    }

    public function test_it_resolves_the_user_for_measurement_device_contract_via_the_device(): void
    {
        $user = new User();
        $user->forceFill([User::name => 'Max']);

        $device = new MeasurementDevice();
        $device->setRelation('user', $user);

        $contract = new MeasurementDeviceContract();
        $contract->setRelation(MeasurementDeviceContract::belongs_to_measurement_device, $device);

        $resolved = $this->registry->forModel(MeasurementDeviceContract::class)?->resolveUser($contract);

        $this->assertSame($user, $resolved);
    }

    public function test_url_resolution_returns_null_for_unpersisted_records(): void
    {
        $this->assertNull($this->registry->forModel(FixedCost::class)?->resolveUrl(new FixedCost()));
        $this->assertNull($this->registry->forModel(Insurance::class)?->resolveUrl(new Insurance()));
        $this->assertNull($this->registry->forModel(Article::class)?->resolveUrl(new Article()));
        $this->assertNull($this->registry->forModel(MeasurementDeviceContract::class)?->resolveUrl(new MeasurementDeviceContract()));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new ReminderTargetRegistry();
    }
}
