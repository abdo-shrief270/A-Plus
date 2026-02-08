<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StudentImportService
{
    /**
     * Import a single student.
     *
     * @param array $data
     * @param User $createdBy
     * @return Student
     * @throws ValidationException
     */
    public function importSingle(array $data, User $createdBy): Student
    {
        $this->validateStudentData($data);

        return DB::transaction(function () use ($data, $createdBy) {
            // Create user first
            $user = User::create([
                'name' => $data['name'],
                'user_name' => $data['user_name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password'] ?? 'password123'),
                'type' => 'student',
                'gender' => $data['gender'] ?? 'male',
            ]);

            // Create student
            $student = Student::create([
                'user_id' => $user->id,
                'exam_id' => $data['exam_id'] ?? null,
                'exam_date' => $data['exam_date'] ?? null,
                'id_number' => $data['id_number'] ?? null,
            ]);

            // Link to school if applicable
            if ($createdBy->type === 'school' && $createdBy->studentSchool) {
                \App\Models\StudentSchool::create([
                    'student_id' => $student->id,
                    'school_id' => $createdBy->studentSchool->school_id,
                ]);
            }

            // Link to parent if applicable
            if ($createdBy->type === 'parent') {
                \App\Models\StudentParent::create([
                    'student_id' => $student->id,
                    'parent_id' => $createdBy->id,
                ]);
            }

            return $student->load(['user', 'league']);
        });
    }

    /**
     * Import multiple students from JSON array.
     *
     * @param array $studentsData
     * @param User $createdBy
     * @return array ['created' => [], 'failed' => []]
     */
    public function importBulk(array $studentsData, User $createdBy): array
    {
        $created = [];
        $failed = [];

        foreach ($studentsData as $index => $data) {
            try {
                $student = $this->importSingle($data, $createdBy);
                $created[] = [
                    'index' => $index,
                    'student_id' => $student->id,
                    'user_name' => $student->user->user_name,
                ];
            } catch (\Exception $e) {
                $failed[] = [
                    'index' => $index,
                    'data' => $data,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'created' => $created,
            'failed' => $failed,
            'total_created' => count($created),
            'total_failed' => count($failed),
        ];
    }

    /**
     * Import students from a CSV file.
     *
     * @param UploadedFile $file
     * @param User $createdBy
     * @return array ['created' => [], 'failed' => []]
     */
    public function importFromFile(UploadedFile $file, User $createdBy): array
    {
        $studentsData = $this->parseFile($file);
        return $this->importBulk($studentsData, $createdBy);
    }

    /**
     * Parse uploaded file into array of student data.
     *
     * @param UploadedFile $file
     * @return array
     */
    protected function parseFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'csv') {
            return $this->parseCsv($file);
        }

        if (in_array($extension, ['xlsx', 'xls'])) {
            return $this->parseExcel($file);
        }

        throw new \InvalidArgumentException('Unsupported file format. Use CSV or Excel.');
    }

    /**
     * Parse CSV file.
     *
     * @param UploadedFile $file
     * @return array
     */
    protected function parseCsv(UploadedFile $file): array
    {
        $data = [];
        $handle = fopen($file->getPathname(), 'r');

        // Read header row
        $headers = fgetcsv($handle);
        $headers = array_map('trim', $headers);
        $headers = array_map('strtolower', $headers);

        while (($row = fgetcsv($handle)) !== false) {
            $studentData = array_combine($headers, $row);
            $data[] = $studentData;
        }

        fclose($handle);
        return $data;
    }

    /**
     * Parse Excel file.
     *
     * @param UploadedFile $file
     * @return array
     */
    protected function parseExcel(UploadedFile $file): array
    {
        // For Excel support, you'd need maatwebsite/excel package
        // For now, return empty array with a note
        throw new \InvalidArgumentException('Excel import requires maatwebsite/excel package. Please use CSV format or install the package.');
    }

    /**
     * Validate student data.
     *
     * @param array $data
     * @throws ValidationException
     */
    protected function validateStudentData(array $data): void
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'user_name' => 'required|string|max:255|unique:users,user_name',
            'email' => 'nullable|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'gender' => 'nullable|in:male,female',
            'exam_id' => 'nullable|exists:exams,id',
            'exam_date' => 'nullable|date',
            'id_number' => 'nullable|string|max:50',
            'password' => 'nullable|string|min:6',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
