<?php

namespace App\Console\Commands;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Exception;
use Faker\Factory as Faker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EmployeeCreateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'employee:create {count=1} {--test-exception=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate dummy employee data with realistic values';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = (int) $this->argument('count');

        if ($count <= 0) {
            $this->error('Count must be a positive integer.');

            return Command::FAILURE;
        }

        $faker = Faker::create();

        // Check existing employee count
        $existingCount = Employee::count();
        $this->info("Found {$existingCount} existing employees.");

        // Define realistic departments and positions
        $departmentPositions = [
            'Engineering' => ['Software Engineer', 'Senior Software Engineer', 'Tech Lead', 'Principal Engineer', 'DevOps Engineer', 'QA Engineer'],
            'Sales' => ['Sales Representative', 'Sales Manager', 'Account Executive', 'Business Development Manager'],
            'Marketing' => ['Marketing Coordinator', 'Marketing Manager', 'Content Writer', 'SEO Specialist', 'Social Media Manager'],
            'HR' => ['HR Coordinator', 'HR Manager', 'Recruiter', 'HR Director'],
            'Finance' => ['Accountant', 'Financial Analyst', 'Finance Manager', 'CFO'],
            'Operations' => ['Operations Manager', 'Project Coordinator', 'Office Manager', 'Operations Director'],
            'Customer Support' => ['Support Agent', 'Support Manager', 'Customer Success Manager'],
            'Product' => ['Product Manager', 'Product Owner', 'UX Designer', 'UI Designer'],
        ];

        // Create departments and positions if they don't exist
        $departmentModels = [];
        $positionModels = [];

        foreach ($departmentPositions as $deptName => $positions) {
            $dept = Department::firstOrCreate(['name' => $deptName]);
            $departmentModels[$deptName] = $dept;

            foreach ($positions as $posName) {
                $pos = Position::firstOrCreate(['name' => $posName]);
                $positionModels[$posName] = $pos;
            }
        }

        // Calculate max possible unique emails
        $maxPossibleEmails = 10000; // Reasonable limit for dummy data
        $availableSlots = $maxPossibleEmails - $existingCount;

        if ($availableSlots <= 0) {
            $this->error('Maximum unique email limit reached. Cannot generate more employees.');

            return Command::FAILURE;
        }

        if ($count > $availableSlots) {
            $this->warn("Requested {$count} employees, but only {$availableSlots} unique emails available.");
            $count = $availableSlots;
        }

        $this->info("Generating {$count} dummy employees...");

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        $created = 0;
        $failed = 0;

        $testExceptionOn = (int) $this->option('test-exception');

        // Use transaction for better performance
        DB::transaction(function () use ($count, $faker, $departmentPositions, $departmentModels, $positionModels, &$created, &$failed, $progressBar, $testExceptionOn) {
            for ($i = 0; $i < $count; $i++) {
                try {
                    // For testing: force exception on specific iteration
                    if ($testExceptionOn > 0 && ($i + 1) === $testExceptionOn) {
                        throw new Exception('Test exception for coverage');
                    }

                    // Select random department and position
                    $departmentName = array_rand($departmentPositions);
                    $positionName = $departmentPositions[$departmentName][array_rand($departmentPositions[$departmentName])];

                    $department = $departmentModels[$departmentName];
                    $position = $positionModels[$positionName];

                    // Generate unique email
                    $email = $this->generateUniqueEmail($faker);

                    // Generate realistic salary based on position
                    $salary = $this->generateSalaryForPosition($positionName, $faker);

                    // Generate hire date within last 5 years
                    $hireDate = $faker->dateTimeBetween('-5 years', 'now');

                    Employee::create([
                        'name' => $faker->name,
                        'email' => $email,
                        'phone' => $faker->optional()->phoneNumber,
                        'department_id' => $department->id,
                        'position_id' => $position->id,
                        'salary' => $salary,
                        'hire_date' => $hireDate->format('Y-m-d'),
                    ]);

                    $created++;
                    $progressBar->advance();

                } catch (Exception $e) {
                    $failed++;
                    $this->error('Failed to create employee: ' . $e->getMessage());
                }
            }
        });

        $progressBar->finish();
        $this->newLine();

        $this->info("✅ Successfully created {$created} employees.");

        if ($failed > 0) {
            $this->warn("⚠️ Failed to create {$failed} employees.");
        }

        $this->info('📊 Total employees in database: ' . Employee::count());

        return Command::SUCCESS;
    }

    /**
     * Generate a unique email address
     */
    private function generateUniqueEmail($faker)
    {
        $maxAttempts = 100;
        $attempts = 0;

        do {
            $email = $faker->unique()->safeEmail;
            $exists = Employee::where('email', $email)->exists();
            $attempts++;

            if ($attempts >= $maxAttempts) {
                // Fallback to timestamp-based email if unique generation fails
                $email = 'employee_' . time() . '_' . rand(1000, 9999) . '@example.com';
                break;
            }
        } while ($exists);

        return $email;
    }

    /**
     * Generate realistic salary based on position
     */
    private function generateSalaryForPosition($position, $faker)
    {
        $salaryRanges = [
            'Software Engineer' => [50000, 90000],
            'Senior Software Engineer' => [80000, 130000],
            'Tech Lead' => [110000, 160000],
            'Principal Engineer' => [140000, 200000],
            'DevOps Engineer' => [70000, 120000],
            'QA Engineer' => [45000, 85000],
            'Sales Representative' => [40000, 75000],
            'Sales Manager' => [70000, 110000],
            'Account Executive' => [60000, 100000],
            'Business Development Manager' => [75000, 120000],
            'Marketing Coordinator' => [40000, 65000],
            'Marketing Manager' => [65000, 95000],
            'Content Writer' => [35000, 60000],
            'SEO Specialist' => [45000, 75000],
            'Social Media Manager' => [45000, 70000],
            'HR Coordinator' => [40000, 60000],
            'HR Manager' => [60000, 90000],
            'Recruiter' => [45000, 75000],
            'HR Director' => [90000, 130000],
            'Accountant' => [45000, 75000],
            'Financial Analyst' => [55000, 85000],
            'Finance Manager' => [75000, 110000],
            'CFO' => [120000, 250000],
            'Operations Manager' => [60000, 95000],
            'Project Coordinator' => [45000, 70000],
            'Office Manager' => [40000, 65000],
            'Operations Director' => [90000, 140000],
            'Support Agent' => [35000, 55000],
            'Support Manager' => [55000, 80000],
            'Customer Success Manager' => [60000, 90000],
            'Product Manager' => [80000, 130000],
            'Product Owner' => [70000, 110000],
            'UX Designer' => [55000, 90000],
            'UI Designer' => [50000, 85000],
        ];

        $range = $salaryRanges[$position] ?? [40000, 80000];

        return number_format($faker->numberBetween($range[0], $range[1]), 2, '.', '');
    }
}
