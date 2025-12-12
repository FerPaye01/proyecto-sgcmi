<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\CargoItem;
use App\Models\CargoManifest;
use App\Models\TarjaNote;
use App\Models\WeighTicket;
use App\Models\YardLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CargoManagementModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_cargo_manifest_can_be_created(): void
    {
        $manifest = CargoManifest::factory()->create();

        $this->assertDatabaseHas('portuario.cargo_manifest', [
            'id' => $manifest->id,
            'manifest_number' => $manifest->manifest_number,
        ]);
    }

    public function test_yard_location_can_be_created(): void
    {
        $yard = YardLocation::factory()->create();

        $this->assertDatabaseHas('portuario.yard_location', [
            'id' => $yard->id,
            'zone_code' => $yard->zone_code,
        ]);
    }

    public function test_cargo_item_can_be_created(): void
    {
        $item = CargoItem::factory()->create();

        $this->assertDatabaseHas('portuario.cargo_item', [
            'id' => $item->id,
            'item_number' => $item->item_number,
        ]);
    }

    public function test_tarja_note_can_be_created(): void
    {
        $tarja = TarjaNote::factory()->create();

        $this->assertDatabaseHas('portuario.tarja_note', [
            'id' => $tarja->id,
            'tarja_number' => $tarja->tarja_number,
        ]);
    }

    public function test_weigh_ticket_can_be_created(): void
    {
        $ticket = WeighTicket::factory()->create();

        $this->assertDatabaseHas('portuario.weigh_ticket', [
            'id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
        ]);
    }

    public function test_weigh_ticket_calculates_net_weight_correctly(): void
    {
        $grossWeight = 10000.50;
        $tareWeight = 2000.25;
        $expectedNet = $grossWeight - $tareWeight;

        $ticket = WeighTicket::factory()->create([
            'gross_weight_kg' => $grossWeight,
            'tare_weight_kg' => $tareWeight,
        ]);

        $this->assertEquals($expectedNet, $ticket->net_weight_kg);
    }

    public function test_cargo_manifest_has_vessel_call_relationship(): void
    {
        $manifest = CargoManifest::factory()->create();

        $this->assertInstanceOf(\App\Models\VesselCall::class, $manifest->vesselCall);
    }

    public function test_cargo_item_has_manifest_relationship(): void
    {
        $item = CargoItem::factory()->create();

        $this->assertInstanceOf(CargoManifest::class, $item->manifest);
    }

    public function test_cargo_item_has_yard_location_relationship(): void
    {
        $item = CargoItem::factory()->create([
            'yard_location_id' => YardLocation::factory(),
        ]);

        $this->assertInstanceOf(YardLocation::class, $item->yardLocation);
    }

    public function test_yard_location_full_code_attribute(): void
    {
        $yard = YardLocation::factory()->create([
            'zone_code' => 'A',
            'block_code' => '01',
            'row_code' => 'R1',
            'tier' => 3,
        ]);

        $this->assertEquals('A-01-R1-T3', $yard->full_location_code);
    }

    public function test_yard_location_available_scope(): void
    {
        YardLocation::factory()->create(['occupied' => true]);
        YardLocation::factory()->create(['occupied' => false]);
        YardLocation::factory()->create(['occupied' => false, 'active' => false]);

        $available = YardLocation::available()->count();

        $this->assertEquals(1, $available);
    }
}
