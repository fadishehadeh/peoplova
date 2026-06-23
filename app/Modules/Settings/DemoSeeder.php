<?php

declare(strict_types=1);

namespace App\Modules\Settings;

use App\Core\Database;
use Throwable;

/**
 * Inserts realistic mockup data for demonstration purposes.
 * Safe to run multiple times — uses INSERT IGNORE / checks before inserting.
 */
final class DemoSeeder
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function run(): void
    {
        $this->db->execute('SET FOREIGN_KEY_CHECKS = 0', []);

        $this->seedLeaveTypes();
        $companyId = $this->seedCompany();
        [$hqBranchId, $dubBranchId] = $this->seedBranches($companyId);
        $deptIds = $this->seedDepartments($companyId, $hqBranchId);
        $titleIds = $this->seedJobTitles($companyId);
        $empIds   = $this->seedEmployees($companyId, $hqBranchId, $dubBranchId, $deptIds, $titleIds);
        $this->seedLeaveBalances($empIds);
        $this->seedLeaveRequests($empIds);
        $this->seedAnnouncements($companyId);

        $this->db->execute('SET FOREIGN_KEY_CHECKS = 1', []);
    }

    // ------------------------------------------------------------------ //

    private function seedLeaveTypes(): void
    {
        // [name, code, default_days, carry_forward_allowed, is_paid, description]
        $types = [
            ['Annual Leave',    'annual',    20, 1, 1, 'Yearly paid leave entitlement'],
            ['Sick Leave',      'sick',      10, 0, 1, 'Medical / health-related leave'],
            ['Emergency Leave', 'emergency',  3, 0, 1, 'Urgent personal circumstances'],
            ['Maternity Leave', 'maternity', 90, 0, 1, 'Paid leave for childbirth'],
            ['Unpaid Leave',    'unpaid',     0, 0, 0, 'Leave without pay'],
        ];

        foreach ($types as [$name, $code, $days, $carryFwd, $isPaid, $desc]) {
            $exists = $this->db->fetchValue('SELECT id FROM leave_types WHERE code = :c', ['c' => $code]);
            if ($exists) continue;

            $this->db->execute(
                'INSERT INTO leave_types (name, code, default_days, carry_forward_allowed, is_paid, description, status)
                 VALUES (:n, :c, :d, :cf, :ip, :desc, "active")',
                ['n' => $name, 'c' => $code, 'd' => $days, 'cf' => $carryFwd, 'ip' => $isPaid, 'desc' => $desc]
            );
        }
    }

    private function seedCompany(): int
    {
        $existing = $this->db->fetchValue('SELECT id FROM companies WHERE is_main_tenant = 1 LIMIT 1');
        if ($existing) return (int) $existing;

        $this->db->execute(
            'INSERT INTO companies (name, legal_name, code, email, phone, address_line_1, city, country, timezone, status, is_main_tenant)
             VALUES ("Peoplova Demo", "Peoplova Demo Inc.", "PDEMO", "admin@peoplova.demo", "+1 555 000 1000",
                     "1 Innovation Drive", "San Francisco", "United States", "America/Los_Angeles", "active", 1)',
            []
        );
        return (int) $this->db->fetchValue('SELECT LAST_INSERT_ID()', []);
    }

    /** @return int[] [hqId, dubId] */
    private function seedBranches(int $companyId): array
    {
        $hqId = (int) ($this->db->fetchValue('SELECT id FROM branches WHERE company_id = :c AND code = "HQ"', ['c' => $companyId]) ?: 0);
        if (!$hqId) {
            $this->db->execute(
                'INSERT INTO branches (company_id, name, code, city, country, status)
                 VALUES (:c, "Headquarters", "HQ", "San Francisco", "United States", "active")',
                ['c' => $companyId]
            );
            $hqId = (int) $this->db->fetchValue('SELECT LAST_INSERT_ID()', []);
        }

        $dubId = (int) ($this->db->fetchValue('SELECT id FROM branches WHERE company_id = :c AND code = "DXB"', ['c' => $companyId]) ?: 0);
        if (!$dubId) {
            $this->db->execute(
                'INSERT INTO branches (company_id, name, code, city, country, status)
                 VALUES (:c, "Dubai Office", "DXB", "Dubai", "United Arab Emirates", "active")',
                ['c' => $companyId]
            );
            $dubId = (int) $this->db->fetchValue('SELECT LAST_INSERT_ID()', []);
        }

        return [$hqId, $dubId];
    }

    /** @return array<string,int> */
    private function seedDepartments(int $companyId, int $branchId): array
    {
        $depts = [
            'engineering' => ['Engineering',       'ENG'],
            'hr'          => ['Human Resources',   'HR'],
            'finance'     => ['Finance',            'FIN'],
            'operations'  => ['Operations',         'OPS'],
            'product'     => ['Product & Design',   'PRD'],
        ];

        $ids = [];
        foreach ($depts as $key => [$name, $code]) {
            $id = $this->db->fetchValue(
                'SELECT id FROM departments WHERE company_id = :c AND code = :code',
                ['c' => $companyId, 'code' => $code]
            );
            if (!$id) {
                $this->db->execute(
                    'INSERT INTO departments (company_id, branch_id, name, code, status)
                     VALUES (:c, :b, :n, :code, "active")',
                    ['c' => $companyId, 'b' => $branchId, 'n' => $name, 'code' => $code]
                );
                $id = $this->db->fetchValue('SELECT LAST_INSERT_ID()', []);
            }
            $ids[$key] = (int) $id;
        }

        return $ids;
    }

    /** @return array<string,int> */
    private function seedJobTitles(int $companyId): array
    {
        // job_titles is company-agnostic (no company_id column)
        $titles = [
            'swe'           => ['Software Engineer',        'SWE'],
            'senior_swe'    => ['Senior Software Engineer', 'SSWE'],
            'hr_manager'    => ['HR Manager',               'HRM'],
            'hr_specialist' => ['HR Specialist',            'HRS'],
            'fin_analyst'   => ['Finance Analyst',          'FINA'],
            'ops_manager'   => ['Operations Manager',       'OPSM'],
            'product_mgr'   => ['Product Manager',          'PRDM'],
            'designer'      => ['UX Designer',              'UXDS'],
        ];

        $ids = [];
        foreach ($titles as $key => [$name, $code]) {
            $id = $this->db->fetchValue(
                'SELECT id FROM job_titles WHERE code = :code',
                ['code' => $code]
            );
            if (!$id) {
                $this->db->execute(
                    'INSERT INTO job_titles (name, code, status) VALUES (:n, :code, "active")',
                    ['n' => $name, 'code' => $code]
                );
                $id = $this->db->fetchValue('SELECT LAST_INSERT_ID()', []);
            }
            $ids[$key] = (int) $id;
        }

        return $ids;
    }

    /** @return int[] */
    private function seedEmployees(int $companyId, int $hqId, int $dubId, array $deptIds, array $titleIds): array
    {
        $employees = [
            ['EMP001', $deptIds['engineering'], $hqId, $titleIds['senior_swe'],  'Sarah',    'Ahmed',       'F', '2021-03-01', 'sarah.ahmed@peoplova.demo'],
            ['EMP002', $deptIds['engineering'], $hqId, $titleIds['swe'],         'James',    'Wilson',      'M', '2022-07-15', 'james.wilson@peoplova.demo'],
            ['EMP003', $deptIds['hr'],          $hqId, $titleIds['hr_manager'],  'Fatima',   'Al-Rashidi',  'F', '2020-01-10', 'fatima.alrashidi@peoplova.demo'],
            ['EMP004', $deptIds['finance'],     $hqId, $titleIds['fin_analyst'], 'Mohammed', 'Hassan',      'M', '2021-09-20', 'mohammed.hassan@peoplova.demo'],
            ['EMP005', $deptIds['operations'],  $dubId, $titleIds['ops_manager'],'Emma',     'Thompson',    'F', '2019-06-05', 'emma.thompson@peoplova.demo'],
            ['EMP006', $deptIds['engineering'], $hqId, $titleIds['swe'],         'Khalid',   'Al-Mansouri', 'M', '2023-02-14', 'khalid.almansouri@peoplova.demo'],
            ['EMP007', $deptIds['product'],     $hqId, $titleIds['product_mgr'], 'Nour',     'Khalil',      'F', '2022-04-01', 'nour.khalil@peoplova.demo'],
            ['EMP008', $deptIds['product'],     $hqId, $titleIds['designer'],    'David',    'Chen',        'M', '2022-11-07', 'david.chen@peoplova.demo'],
            ['EMP009', $deptIds['hr'],          $dubId, $titleIds['hr_specialist'],'Aisha',  'Malik',       'F', '2023-08-21', 'aisha.malik@peoplova.demo'],
            ['EMP010', $deptIds['finance'],     $dubId, $titleIds['fin_analyst'], 'Lucas',   'Fernandez',   'M', '2021-12-03', 'lucas.fernandez@peoplova.demo'],
        ];

        $ids = [];
        foreach ($employees as [$code, $deptId, $branchId, $titleId, $first, $last, $gender, $joined, $email]) {
            $existing = $this->db->fetchValue('SELECT id FROM employees WHERE employee_code = :c', ['c' => $code]);
            if ($existing) {
                $ids[] = (int) $existing;
                continue;
            }

            $genderVal = $gender === 'F' ? 'female' : 'male';
            $this->db->execute(
                'INSERT INTO employees
                    (employee_code, company_id, branch_id, department_id, job_title_id,
                     first_name, last_name, work_email, gender, employment_type,
                     joining_date, employee_status, created_by)
                 VALUES
                    (:code, :company, :branch, :dept, :title,
                     :first, :last, :email, :gender, "full_time",
                     :joined, "active", 1)',
                [
                    'code'    => $code,
                    'company' => $companyId,
                    'branch'  => $branchId,
                    'dept'    => $deptId,
                    'title'   => $titleId,
                    'first'   => $first,
                    'last'    => $last,
                    'email'   => $email,
                    'gender'  => $genderVal,
                    'joined'  => $joined,
                ]
            );
            $ids[] = (int) $this->db->fetchValue('SELECT LAST_INSERT_ID()', []);
        }

        return $ids;
    }

    /** @param int[] $empIds */
    private function seedLeaveBalances(array $empIds): void
    {
        $leaveTypes = $this->db->fetchAll(
            'SELECT id, code, default_days FROM leave_types WHERE status = "active"',
            []
        );
        $year = (int) date('Y');

        foreach ($empIds as $empId) {
            foreach ($leaveTypes as $lt) {
                $exists = $this->db->fetchValue(
                    'SELECT id FROM leave_balances WHERE employee_id = :e AND leave_type_id = :l AND balance_year = :y',
                    ['e' => $empId, 'l' => $lt['id'], 'y' => $year]
                );
                if ($exists) continue;

                $this->db->execute(
                    'INSERT INTO leave_balances (employee_id, leave_type_id, balance_year, opening_balance, accrued, used_amount, adjusted_amount, closing_balance)
                     VALUES (:e, :l, :y, :opening, :accrued, 0, 0, :closing)',
                    ['e' => $empId, 'l' => $lt['id'], 'y' => $year, 'opening' => $lt['default_days'], 'accrued' => $lt['default_days'], 'closing' => $lt['default_days']]
                );
            }
        }
    }

    /** @param int[] $empIds */
    private function seedLeaveRequests(array $empIds): void
    {
        if (empty($empIds)) return;

        $annualId = $this->db->fetchValue('SELECT id FROM leave_types WHERE code = "annual"');
        $sickId   = $this->db->fetchValue('SELECT id FROM leave_types WHERE code = "sick"');
        if (!$annualId || !$sickId) return;

        $requests = [
            [$empIds[0], $annualId, date('Y') . '-07-14', date('Y') . '-07-18', 5,  'approved',        'Summer holiday'],
            [$empIds[1], $sickId,   date('Y') . '-06-03', date('Y') . '-06-04', 2,  'approved',        'Flu recovery'],
            [$empIds[2], $annualId, date('Y') . '-08-01', date('Y') . '-08-05', 5,  'pending_manager', 'Family vacation'],
            [$empIds[3], $annualId, date('Y') . '-09-10', date('Y') . '-09-10', 1,  'pending_hr',      'Personal appointment'],
            [$empIds[4], $sickId,   date('Y') . '-05-20', date('Y') . '-05-21', 2,  'rejected',        'Medical leave'],
        ];

        foreach ($requests as [$empId, $typeId, $start, $end, $days, $status, $reason]) {
            $exists = $this->db->fetchValue(
                'SELECT id FROM leave_requests WHERE employee_id = :e AND start_date = :s AND leave_type_id = :l',
                ['e' => $empId, 's' => $start, 'l' => $typeId]
            );
            if ($exists) continue;

            $this->db->execute(
                'INSERT INTO leave_requests
                    (employee_id, leave_type_id, start_date, end_date, days_requested, status, reason, submitted_at)
                 VALUES
                    (:e, :l, :s, :end, :d, :st, :r, NOW())',
                ['e' => $empId, 'l' => $typeId, 's' => $start, 'end' => $end,
                 'd' => $days, 'st' => $status, 'r' => $reason]
            );
        }
    }

    private function seedAnnouncements(int $companyId): void
    {
        // announcements has: title, content, priority, starts_at, ends_at, status, created_by
        // no company_id, no category, no body, no publish_at
        $announcements = [
            [
                'Welcome to Peoplova Demo',
                '<p>Welcome to the <strong>Peoplova</strong> HR platform demo. This environment contains sample employees, departments, and workflows so you can explore the full feature set.</p><p>Feel free to test leave requests, document uploads, onboarding flows, and all other modules.</p>',
                'high',
            ],
            [
                'Q3 Performance Reviews Starting Soon',
                '<p>Our Q3 performance review cycle begins next month. All managers should complete self-assessments for their direct reports before the end of the month.</p><p>Contact HR for the review guidelines and templates.</p>',
                'normal',
            ],
            [
                'Remote Work Policy Update',
                '<p>Effective next quarter, all employees are eligible for up to <strong>3 days of remote work</strong> per week. Please coordinate with your manager to align schedules with your team.</p>',
                'normal',
            ],
        ];

        foreach ($announcements as [$title, $content, $priority]) {
            $exists = $this->db->fetchValue(
                'SELECT id FROM announcements WHERE title = :t',
                ['t' => $title]
            );
            if ($exists) continue;

            $this->db->execute(
                'INSERT INTO announcements
                    (title, content, priority, status, starts_at, created_by)
                 VALUES
                    (:t, :b, :p, "published", NOW(), 1)',
                ['t' => $title, 'b' => $content, 'p' => $priority]
            );
        }
    }
}
