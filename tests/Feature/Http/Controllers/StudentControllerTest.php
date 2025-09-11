<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\StudentController
 */
final class StudentControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $students = Student::factory()->count(3)->create();

        $response = $this->get(route('students.index'));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\StudentController::class,
            'store',
            \App\Http\Requests\StudentStoreRequest::class
        );
    }

    #[Test]
    public function store_saves(): void
    {
        $barcode = fake()->word();
        $full_name = fake()->word();
        $grade_level = fake()->word();

        $response = $this->post(route('students.store'), [
            'barcode' => $barcode,
            'full_name' => $full_name,
            'grade_level' => $grade_level,
        ]);

        $students = Student::query()
            ->where('barcode', $barcode)
            ->where('full_name', $full_name)
            ->where('grade_level', $grade_level)
            ->get();
        $this->assertCount(1, $students);
        $student = $students->first();

        $response->assertCreated();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $student = Student::factory()->create();

        $response = $this->get(route('students.show', $student));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\StudentController::class,
            'update',
            \App\Http\Requests\StudentUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $student = Student::factory()->create();
        $barcode = fake()->word();
        $full_name = fake()->word();
        $grade_level = fake()->word();

        $response = $this->put(route('students.update', $student), [
            'barcode' => $barcode,
            'full_name' => $full_name,
            'grade_level' => $grade_level,
        ]);

        $student->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($barcode, $student->barcode);
        $this->assertEquals($full_name, $student->full_name);
        $this->assertEquals($grade_level, $student->grade_level);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $student = Student::factory()->create();

        $response = $this->delete(route('students.destroy', $student));

        $response->assertNoContent();

        $this->assertModelMissing($student);
    }
}
