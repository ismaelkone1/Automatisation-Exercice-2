<?php

namespace App\Console;

use Illuminate\Support\Facades\Schema;
use Slim\App;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Faker\Factory as Faker;

class PopulateDatabaseCommand extends Command
{
    private App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('db:populate');
        $this->setDescription('Populate database with random data');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Populating database with random data...');

        /** @var \Illuminate\Database\Capsule\Manager $db */
        $db = $this->app->getContainer()->get('db');

        $db->getConnection()->statement("SET FOREIGN_KEY_CHECKS=0");
        $db->getConnection()->statement("TRUNCATE `employees`");
        $db->getConnection()->statement("TRUNCATE `offices`");
        $db->getConnection()->statement("TRUNCATE `companies`");
        $db->getConnection()->statement("SET FOREIGN_KEY_CHECKS=1");

        $faker = Faker::create();
        $currentTimestamp = date('Y-m-d H:i:s');

        // Generate companies
        $companies = [];
        for ($i = 1; $i <= 4; $i++) {
            $companies[] = [
                'id' => $i,
                'name' => $faker->company,
                'phone' => $faker->phoneNumber,
                'email' => $faker->companyEmail,
                'website' => $faker->url,
                'image' => $faker->imageUrl(400, 300, 'business', true, 'Logo'),
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
                'head_office_id' => null, // will be set later
            ];
        }

        foreach ($companies as $company) {
            $db->getConnection()->table('companies')->insert($company);
        }

        // Generate offices
        $offices = [];
        $officeId = 1;
        foreach ($companies as $company) {
            for ($j = 0; $j < random_int(2, 3); $j++) {
                $offices[] = [
                    'id' => $officeId++,
                    'name' => $faker->streetName,
                    'address' => $faker->address,
                    'city' => $faker->city,
                    'zip_code' => $faker->postcode,
                    'country' => $faker->country,
                    'email' => $faker->email,
                    'phone' => $faker->phoneNumber,
                    'company_id' => $company['id'],
                    'created_at' => $currentTimestamp,
                    'updated_at' => $currentTimestamp,
                ];
            }
        }

        foreach ($offices as $office) {
            $db->getConnection()->table('offices')->insert($office);
        }

        // Generate employees
        $employees = [];
        $employeeId = 1;
        foreach ($offices as $office) {
            for ($k = 0; $k < random_int(2, 5); $k++) {
                $employees[] = [
                    'id' => $employeeId++,
                    'first_name' => $faker->firstName,
                    'last_name' => $faker->lastName,
                    'office_id' => $office['id'],
                    'email' => $faker->email,
                    'phone' => $faker->phoneNumber,
                    'job_title' => $faker->jobTitle,
                    'created_at' => $currentTimestamp,
                    'updated_at' => $currentTimestamp,
                ];
            }
        }

        foreach ($employees as $employee) {
            $db->getConnection()->table('employees')->insert($employee);
        }

        // Set head offices for companies
        foreach ($companies as &$company) {
            $companyOffices = array_filter($offices, fn($office) => $office['company_id'] === $company['id']);
            $headOffice = array_shift($companyOffices); // Pick the first office as the head office
            $company['head_office_id'] = $headOffice['id']; // Set the head office id
            $db->getConnection()->table('companies')
                ->where('id', $company['id'])
                ->update(['head_office_id' => $headOffice['id']]);
        }

        $output->writeln('Database populated successfully!');
        return Command::SUCCESS;
    }
}
