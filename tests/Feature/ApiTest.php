<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\Item;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_protected_routes_require_authentication(): void
    {
        $this->getJson('/api/schools')->assertUnauthorized();
        $this->postJson('/api/schools', ['name' => 'Escola Teste'])->assertUnauthorized();
    }

    public function test_login_returns_token_and_user(): void
    {
        $user = $this->admin();

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'email', 'role']])
            ->assertJsonPath('user.id', $user->id);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = $this->admin();

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'senha-errada',
        ])->assertUnauthorized();
    }

    public function test_session_returns_authenticated_user(): void
    {
        $user = $this->admin();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/auth/session')
            ->assertOk()
            ->assertJsonPath('email', $user->email);
    }

    public function test_school_crud_flow(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/schools', [
                'name' => 'Escola de Teste',
                'city' => 'São Paulo',
                'state' => 'SP',
                'schedule_type' => 'semanal',
            ])
            ->assertCreated()
            ->assertJsonPath('name', 'Escola de Teste');

        $schoolId = $response->json('id');

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/schools')
            ->assertOk()
            ->assertJsonCount(1);

        $this->actingAs($this->admin(), 'sanctum')
            ->putJson("/api/schools/{$schoolId}", ['name' => 'Escola Renomeada'])
            ->assertOk()
            ->assertJsonPath('name', 'Escola Renomeada');

        $this->actingAs($this->admin(), 'sanctum')
            ->deleteJson("/api/schools/{$schoolId}")
            ->assertNoContent();
    }

    public function test_class_room_schedule_flow(): void
    {
        $school = School::create(['name' => 'Escola']);

        $class = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/classes', [
                'school_id' => $school->id,
                'nap' => 'NAP 1',
                'name' => 'Fundamental I',
                'year' => '2026',
            ])
            ->assertCreated()
            ->json();

        $room = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/rooms', [
                'class_id' => $class['id'],
                'name' => 'Sala 101',
                'student_count' => 25,
            ])
            ->assertCreated()
            ->json();

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/schedules', [
                'class_id' => $class['id'],
                'room_id' => $room['id'],
                'day_of_week' => 1,
                'start_time' => '07:30',
                'end_time' => '09:00',
                'subject' => 'Matemática',
                'teacher' => 'Prof. Ana',
                'fortnight' => 1,
            ])
            ->assertCreated()
            ->assertJsonPath('subject', 'Matemática');

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/schedules?class_id='.$class['id'])
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_agenda_with_orientador_association(): void
    {
        $orientador = User::factory()->create(['role' => 'orientador']);

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/agenda', [
                'date' => '2026-09-20',
                'start_time' => '08:00',
                'end_time' => '10:00',
                'activity' => 'Reunião',
                'orientador_ids' => [$orientador->id],
            ])
            ->assertCreated()
            ->assertJsonCount(1, 'orientadores');

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/agenda?orientador_id='.$orientador->id)
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_nap_items_upsert(): void
    {
        $school = School::create(['name' => 'Escola']);
        $item = Item::create([
            'name' => 'Tapete de Atividades',
            'category' => 'tapete',
            'naps' => ['NAP 1', 'NAP 2'],
        ]);

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/nap-items/upsert', [
                'school_id' => $school->id,
                'segment_name' => 'NAP 1',
                'item_id' => $item->id,
                'quantity' => 3,
                'year' => '2026',
            ])
            ->assertCreated();

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/nap-items/upsert', [
                'school_id' => $school->id,
                'segment_name' => 'NAP 1',
                'item_id' => $item->id,
                'quantity' => 5,
                'year' => '2026',
            ])
            ->assertCreated();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/nap-items?school_id='.$school->id)
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.quantity', 5);
    }

    public function test_user_schools_pivot(): void
    {
        $orientador = User::factory()->create(['role' => 'orientador']);
        $school = School::create(['name' => 'Escola']);

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson("/api/users/{$orientador->id}/schools", [
                'school_id' => $school->id,
            ])
            ->assertOk()
            ->assertJsonCount(1);

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/schools/'.$school->id.'/users')
            ->assertOk()
            ->assertJsonCount(1);

        $this->actingAs($this->admin(), 'sanctum')
            ->deleteJson("/api/users/{$orientador->id}/schools/{$school->id}")
            ->assertOk()
            ->assertJsonCount(0);
    }

    public function test_cors_headers_are_applied(): void
    {
        $this->getJson('/api/schools', ['Origin' => 'http://localhost:3000'])
            ->assertUnauthorized()
            ->assertHeader('Access-Control-Allow-Origin');
    }

    public function test_agenda_created_at_defaults_to_now(): void
    {
        $agenda = Agenda::create([
            'date' => '2026-09-20',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'activity' => 'Atividade',
        ]);

        $this->assertNotNull($agenda->created_at);
    }
}
