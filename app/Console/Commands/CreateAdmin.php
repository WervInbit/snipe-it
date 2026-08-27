<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Throwable;

class CreateAdmin extends Command
{
    protected $signature = 'snipeit:create-admin
                            {--first_name= : Administrator first name}
                            {--last_name= : Administrator last name}
                            {--email= : Administrator email address}
                            {--username= : Administrator username}
                            {--password= : Password (prefer the hidden interactive prompt)}
                            {--bootstrap : Refuse to run when any active or deleted user already exists}
                            {show_in_list? : true or false}
                            {autoassign_licenses? : true or false}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a superuser via the command line.';

    public function handle(): int
    {
        if ($this->option('bootstrap') && User::withTrashed()->exists()) {
            $this->error('Bootstrap refused: at least one active or deleted user already exists.');

            return self::FAILURE;
        }

        if (! Setting::getSettings()) {
            $this->error(
                'Administrator creation refused: run migrations and the reviewed foundation seeder first.'
            );

            return self::FAILURE;
        }

        $firstName = $this->requiredOption('first_name', 'First name');
        $lastName = $this->requiredOption('last_name', 'Last name');
        $email = $this->requiredOption('email', 'Email address');
        $username = $this->requiredOption('username', 'Username');
        $password = $this->password();

        if (in_array(null, [$firstName, $lastName, $email, $username, $password], true)) {
            return self::FAILURE;
        }

        try {
            $showInList = $this->booleanArgument('show_in_list');
            $autoassignLicenses = $this->booleanArgument('autoassign_licenses');
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $passwordValidator = Validator::make(
            ['password' => $password],
            ['password' => Setting::passwordComplexityRulesSaving('store')]
        );

        if ($passwordValidator->fails()) {
            foreach ($passwordValidator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = new User();
        $user->first_name = $firstName;
        $user->last_name = $lastName;
        $user->username = $username;
        $user->email = $email;
        $user->permissions = json_encode(['superuser' => 1], JSON_THROW_ON_ERROR);
        $user->password = bcrypt($password);
        $user->activated = 1;

        if ($this->argument('show_in_list') !== null) {
            $user->show_in_list = $showInList;
        }

        if ($this->argument('autoassign_licenses') !== null) {
            $user->autoassign_licenses = $autoassignLicenses;
        }

        try {
            $saved = DB::transaction(fn (): bool => $user->save());
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Administrator creation failed; no user was created.');

            return self::FAILURE;
        }

        if (! $saved) {
            foreach ($user->getErrors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $this->info('Administrator created successfully.');

        return self::SUCCESS;
    }

    private function requiredOption(string $name, string $label): ?string
    {
        $value = trim((string) $this->option($name));

        if ($value !== '') {
            return $value;
        }

        if (! $this->input->isInteractive()) {
            $this->error("The --{$name} option is required in non-interactive mode.");

            return null;
        }

        $answer = trim((string) $this->ask($label));

        if ($answer === '') {
            $this->error("{$label} is required.");

            return null;
        }

        return $answer;
    }

    private function password(): ?string
    {
        $password = (string) $this->option('password');

        if ($password !== '') {
            $this->warn('Supplying --password can expose it in process history; prefer the hidden prompt.');

            return $password;
        }

        if (! $this->input->isInteractive()) {
            $this->error('The --password option is required in non-interactive mode.');

            return null;
        }

        $password = (string) $this->secret('Password');
        $confirmation = (string) $this->secret('Confirm password');

        if ($password === '' || ! hash_equals($password, $confirmation)) {
            $this->error('Password confirmation does not match.');

            return null;
        }

        return $password;
    }

    private function booleanArgument(string $name): ?bool
    {
        $value = $this->argument($name);

        if ($value === null) {
            return null;
        }

        $normalized = strtolower((string) $value);

        if (! in_array($normalized, ['true', 'false'], true)) {
            throw new InvalidArgumentException("The {$name} argument must be true or false.");
        }

        return $normalized === 'true';
    }
}
