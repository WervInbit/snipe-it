<?php

namespace App\Console\Commands;

use App\Support\BackupRestoreFilePlanner;
use enshrined\svgSanitize\Sanitizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use ZipArchive;

class SQLStreamer {
    private $input;
    private $output;
    // embed the prefix here?
    public ?string $prefix;

    private bool $reading_beginning_of_line = true;

    public static $buffer_size = 1024 * 1024;  // use a 1MB buffer, ought to work fine for most cases?

    public array $tablenames = [];
    private bool $should_guess = false;
    private bool $statement_is_permitted = false;

    public function __construct($input, $output, string $prefix = null)
    {
        $this->input = $input;
        $this->output = $output;
        $this->prefix = $prefix;
    }

    public function parse_sql(string $line): string {
        // take into account the 'start of line or not' setting as an instance variable?
        // 'continuation' lines for a permitted statement are PERMITTED.
        // remove *only* line-feeds & carriage-returns; helpful for regexes against lines from
        // Windows dumps
        $line = trim($line, "\r\n");
        if($this->statement_is_permitted && $line[0] === ' ') {
            return $line . "\n"; //re-add the newline
        }

        $table_regex = '`?([a-zA-Z0-9_]+)`?';
        $allowed_statements = [
            "/^(DROP TABLE (?:IF EXISTS )?)`$table_regex(.*)$/" => false,
            "/^(CREATE TABLE )$table_regex(.*)$/" => true, //sets up 'continuation'
            "/^(LOCK TABLES )$table_regex(.*)$/" => false,
            "/^(INSERT INTO )$table_regex(.*)$/" => false,
            "/^UNLOCK TABLES/" => false,
            // "/^\\) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;/" => false, // FIXME not sure what to do here?
            "/^\\)[a-zA-Z0-9_= ]*;$/" => false,
            // ^^^^^^ that bit should *exit* the 'permitted' block
            "/^\\(.*\\)[,;]$/" => false, //older MySQL dump style with one set of values per line
            /* we *could* have made the ^INSERT INTO blah VALUES$ turn on the capturing state, and closed it with
               a ^(blahblah);$ but it's cleaner to not have to manage the state machine. We're just going to
               assume that (blahblah), or (blahblah); are values for INSERT and are always acceptable. */
            "<^/\*!40101 SET NAMES '?[a-zA-Z0-9_-]+'? \*/;$>"                                   => false, //using weird delimiters (<,>) for readability. allow quoted or unquoted charsets
            "<^/\*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' \*/;$>" => false, //same, now handle zero-values
        ];

        foreach($allowed_statements as $statement => $statechange) {
//            $this->info("Checking regex: $statement...\n");
            $matches = [];
            if (preg_match($statement,$line,$matches)) {
                $this->statement_is_permitted = $statechange;
                // matches are: 1 => first part of the statement, 2 => tablename, 3 => rest of statement
                // (with of course 0 being "the whole match")
                if (@$matches[2]) {
//                    print "Found a tablename! It's: ".$matches[2]."\n";
                    if ($this->should_guess) {
                        @$this->tablenames[$matches[2]] += 1;
                        continue; //oh? FIXME
                    } else {
                        $cleaned_tablename = \DB::getTablePrefix().preg_replace('/^'.$this->prefix.'/','',$matches[2]);
                        $line = preg_replace($statement,'$1`'.$cleaned_tablename.'`$3' , $line);
                    }
                } else {
                    // no explicit tablename in this one, leave the line alone
                }
                //how do we *replace* the tablename?
//                print "RETURNING LINE: $line";
                return $line . "\n"; //re-add newline
            }
        }
        // all that is not allowed is denied.
        return "";
    }

    //this is used in exactly *TWO* places, and in both cases should return a prefix I think?
    // first - if you do the --sanitize-only one (which is mostly for testing/development)
    // next - when you run *without* a guessed prefix, this is run first to figure out the prefix
    // I think we have to *duplicate* the call to be able to run it again?
    public static function guess_prefix($input):string
    {
        $parser = new self($input, null);
        $parser->should_guess = true;
        $parser->line_aware_piping(); // <----- THIS is doing the heavy lifting!

        $check_tables = ['settings' => null, 'migrations' => null /* 'assets' => null */]; //TODO - move to statics?
        //can't use 'users' because the 'accessories_checkout' table?
        // can't use 'assets' because 'ver1_components_assets'
        foreach($check_tables as $check_table => $_ignore) {
            foreach ($parser->tablenames as $tablename => $_count) {
//                print "Comparing $tablename to $check_table\n";
                if (str_ends_with($tablename,$check_table)) {
//                    print "Found one!\n";
                    $check_tables[$check_table] = substr($tablename,0,-strlen($check_table));
                }
            }
        }
        $guessed_prefix = null;
        foreach ($check_tables as $clean_table => $prefix_guess) {
            if(is_null($prefix_guess)) {
                throw new RuntimeException("Could not find required table {$clean_table} while guessing the backup prefix.");
            }
            if(is_null($guessed_prefix)) {
                $guessed_prefix = $prefix_guess;
            } else {
                if ($guessed_prefix != $prefix_guess) {
                    throw new RuntimeException("Backup table prefix mismatch: guessed {$guessed_prefix}, then found {$prefix_guess}.");
                }
            }
        }

        return $guessed_prefix;

    }

    public function line_aware_piping(): int
    {
        $bytes_read = 0;
        if (! $this->input) {
            throw new \Exception("No Input available for line_aware_piping");
        }

        while (($buffer = fgets($this->input, SQLStreamer::$buffer_size)) !== false) {
            $bytes_read += strlen($buffer);
            if ($this->reading_beginning_of_line) {
                // Log::debug("Buffer is: '$buffer'");
                $cleaned_buffer = $this->parse_sql($buffer);
                if ($this->output) {
                    $write_offset = 0;
                    while ($write_offset < strlen($cleaned_buffer)) {
                        $bytes_written = fwrite($this->output, substr($cleaned_buffer, $write_offset));
                        if ($bytes_written === false || $bytes_written === 0) {
                            throw new RuntimeException('Unable to write to pipe');
                        }
                        $write_offset += $bytes_written;
                    }
                }
            }
            // if we got a newline at the end of this, then the _next_ read is the beginning of a line
            if($buffer[strlen($buffer)-1] === "\n") {
                $this->reading_beginning_of_line = true;
            } else {
                $this->reading_beginning_of_line = false;
            }

        }
        return $bytes_read;

    }
}



class RestoreFromBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // FIXME - , stripping prefixes and nonstandard SQL statements. Without --prefix, guess and return the correct prefix to strip
    protected $signature = 'snipeit:restore 
                                            {--force : Skip the danger prompt; assuming you enter "y"} 
                                            {filename : The zip file to be migrated}
                                            {--no-progress : Don\'t show a progress bar}
                                            {--sanitize-guess-prefix : Guess and output the table-prefix needed to "sanitize" the SQL}
                                            {--sanitize-with-prefix= : "Sanitize" the SQL, using the passed-in table prefix (can be learned from --sanitize-guess-prefix). Pass as just \'--sanitize-with-prefix=\' to use no prefix}
                                            {--sql-stdout-only : ONLY "Sanitize" the SQL and print it to stdout - useful for debugging - probably requires --sanitize-with-prefix= }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore from a previously created application backup file';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $dir = getcwd();
        if( $dir != base_path() ) { // usually only the case when running via webserver, not via command-line
            Log::debug("Current working directory is: $dir, changing directory to: ".base_path());
            chdir(base_path()); // TODO - is this *safe* to change on a running script?!
        }
        //
        $filename = $this->argument('filename');

        if (! $filename) {
            return $this->commandFailure('Missing required filename');
        }

        if (! $this->option('force') && ! $this->option('sanitize-guess-prefix') && ! $this->confirm('Are you sure you wish to restore from the given backup file? This can lead to MASSIVE DATA LOSS!')) {
            return $this->commandFailure('Data loss not confirmed');
        }

        if (config('database.default') != 'mysql') {
            return $this->commandFailure('DB_CONNECTION must be MySQL in order to perform a restore. Detected: '.config('database.default'));
        }

        $za = new ZipArchive();

        $errcode = $za->open($filename/* , ZipArchive::RDONLY */); // that constant only exists in PHP 7.4 and higher
        if ($errcode !== true) {
            $errors = [
                ZipArchive::ER_EXISTS => 'File already exists.',
                ZipArchive::ER_INCONS => 'Zip archive inconsistent.',
                ZipArchive::ER_INVAL => 'Invalid argument.',
                ZipArchive::ER_MEMORY => 'Malloc failure.',
                ZipArchive::ER_NOENT => 'No such file ('.$filename.') in directory '.$dir.'.',
                ZipArchive::ER_NOZIP => 'Not a zip archive.',
                ZipArchive::ER_OPEN => "Can't open file.",
                ZipArchive::ER_READ => 'Read error.',
                ZipArchive::ER_SEEK => 'Seek error.',
            ];

            return $this->commandFailure(
                'Could not access file: '.$filename.' - '.($errors[$errcode] ?? "Unknown reason: $errcode")
            );
        }

        $private_files = [
            'storage/oauth-private.key',
            'storage/oauth-public.key',
        ];

        $public_files = [
            'public/uploads/logo.*',
            'public/uploads/setting-email_logo*',
            'public/uploads/setting-label_logo*',
            'public/uploads/setting-logo*',
            'public/uploads/favicon.*',
            'public/uploads/favicon-uploaded.*',
        ];

        $sqlfiles = [];
        $sqlfile_indices = [];

        $interesting_files = [];
        $boring_files = [];
        $unsafe_files = [];
        $good_extensions = (array) config('filesystems.allowed_upload_extensions_array', []);

        for ($i = 0; $i < $za->numFiles; $i++) {
            $stat_results = $za->statIndex($i);
            if ($stat_results === false || ! isset($stat_results['name'])) {
                return $this->commandFailure("Unable to read ZIP entry metadata at index {$i}.");
            }

            // echo "index: $i\n";
            // print_r($stat_results);

            $raw_path = $stat_results['name'];
            if (strpos($raw_path, '\\') !== false) { //found a backslash, swap it to forward-slash
                $raw_path = strtr($raw_path, '\\', '/');
                //print "Translating file: ".$stat_results['name']." to: ".$raw_path."\n";
            }

            // skip macOS resource fork files (?!?!?!)
            if (strpos($raw_path, '__MACOSX') !== false && strpos($raw_path, '._') !== false) {
                //print "SKIPPING macOS Resource fork file: $raw_path\n";
                continue;
            }

            if (str_ends_with($raw_path, '/')
                || in_array(basename($raw_path), ['.gitkeep', '.gitignore', '.DS_Store'], true)
            ) {
                continue;
            }

            if (strtolower(pathinfo($raw_path, PATHINFO_EXTENSION)) === 'sql') {
                Log::debug('Found a sql file!');
                $sqlfiles[] = $raw_path;
                $sqlfile_indices[] = $i;
                continue;
            }

            $restore_target = BackupRestoreFilePlanner::targetForArchivePath($raw_path);
            if ($restore_target !== null) {
                if (BackupRestoreFilePlanner::hasAllowedExtension($restore_target, $good_extensions)) {
                    $interesting_files[$raw_path] = ['target' => $restore_target, 'index' => $i];
                } else {
                    $unsafe_files[] = $raw_path;
                    Log::debug('Potentially unsafe file '.$raw_path.' is being skipped');
                }

                continue;
            }

            foreach (['private' => $private_files, 'public' => $public_files] as $files) {
                foreach ($files as $file) {
                    $has_wildcard = (strpos($file, '*') !== false);
                    if ($has_wildcard) {
                        $file = substr($file, 0, -1); //trim last character (which should be the wildcard)
                    }
                    $last_pos = strrpos($raw_path, $file); // no trailing slash!
                    if ($last_pos !== false) {
                        //print("INTERESTING - last_pos is $last_pos when searching $raw_path for $file - last_pos+strlen(\$file) is: ".($last_pos+strlen($file))." and strlen(\$rawpath) is: ".strlen($raw_path)."\n");
                        //no wildcards found in $file, process 'normally'
                        if ($last_pos + strlen($file) == strlen($raw_path) || $has_wildcard) { //again, no trailing slash. or this is a wildcard and we just take it.
                            // print("FOUND THE EXACT FILE: $file AT: $raw_path!!!\n"); //we *do* care about this, though.
                            $restore_target = dirname($file).'/'.basename($raw_path);
                            if (BackupRestoreFilePlanner::hasAllowedExtension($restore_target, $good_extensions)) {
                                $interesting_files[$raw_path] = [
                                    'target' => $restore_target,
                                    'index' => $i,
                                ];
                            } else {
                                $unsafe_files[] = $raw_path;
                                Log::debug('Potentially unsafe file '.$raw_path.' is being skipped');
                            }

                            continue 3;
                        }
                    }
                }
            }
            $boring_files[] = $raw_path; //if we've gotten to here and haven't continue'ed our way into the next iteration, we don't want this file
        } // end of pre-processing the ZIP file for-loop
        // print_r($interesting_files);exit(-1);

        if (count($sqlfiles) != 1) {
            return $this->commandFailure('There should be exactly *one* sql backup file found, found: '.(count($sqlfiles) == 0 ? 'None' : implode(', ', $sqlfiles)));
        }

        if (strpos($sqlfiles[0], 'db-dumps') === false) {
            //return $this->error("SQL backup file is missing 'db-dumps' component of full pathname: ".$sqlfiles[0]);
            //older Snipe-IT installs don't have the db-dumps subdirectory component
        }

        $sql_stat = $za->statIndex($sqlfile_indices[0]);
        if ($sql_stat === false || ! isset($sql_stat['name'])) {
            return $this->commandFailure('Unable to read SQL file metadata from the backup.');
        }

        //$this->info("SQL Stat is: ".print_r($sql_stat,true));
        $sql_contents = $za->getStream($sql_stat['name']); // maybe copy *THIS* thing?
        if ($sql_contents === false) {
            return $this->commandFailure('Unable to open SQL file: '.$sql_stat['name']);
        }

        // OKAY, now that we *found* the sql file if we're doing just the guess-prefix thing, we can do that *HERE* I think?
        if ($this->option('sanitize-guess-prefix')) {
            $prefix = SQLStreamer::guess_prefix($sql_contents);
            $this->line($prefix);

            return $this->info("Re-run this command with '--sanitize-with-prefix=".$prefix."' to see an attempt to sanitize your SQL.");
        }

        // If we're doing --sql-stdout-only, handle that now so we don't have to open pipes to mysql and all of that silliness
        if ($this->option('sql-stdout-only')) {
            $sql_importer = new SQLStreamer($sql_contents, STDOUT, $this->option('sanitize-with-prefix'));
            $bytes_read = $sql_importer->line_aware_piping();
            return $this->warn("$bytes_read total bytes read");
            //TODO - it'd be nice to dump this message to STDERR so that STDOUT is just pure SQL,
            // which would be good for redirecting to a file, and not having to trim the last line off of it
        }

        //how to invoke the restore?
        $pipes = [];

        $env_vars = getenv();
        $env_vars['MYSQL_PWD'] = config('database.connections.mysql.password');
        // TODO notes: we are stealing the dump_binary_path (which *probably* also has your copy of the mysql binary in it. But it might not, so we might need to extend this)
        //             we unilaterally prepend a slash to the `mysql` command. This might mean your path could look like /blah/blah/blah//mysql - which should be fine. But maybe in some environments it isn't?
        $mysql_binary = config('database.connections.mysql.dump.dump_binary_path').\DIRECTORY_SEPARATOR.'mysql'.(\DIRECTORY_SEPARATOR == '\\' ? ".exe" : "");
        if( ! file_exists($mysql_binary) ) {
            return $this->commandFailure("mysql tool at: '$mysql_binary' does not exist, cannot restore. Please edit DB_DUMP_PATH in your .env to point to a directory that contains the mysqldump and mysql binary");
        }
        $mysql_command = escapeshellarg($mysql_binary)
            .' --batch --binary-mode'
            .' -h '.escapeshellarg(config('database.connections.mysql.host'))
            .' -P '.escapeshellarg(config('database.connections.mysql.port'))
            .' -u '.escapeshellarg(config('database.connections.mysql.username'))
            .' '.escapeshellarg(config('database.connections.mysql.database'));
        $proc_results = proc_open($mysql_command, // yanked -p since we pass via ENV
                                   [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                                   $pipes,
                                   null,
                                   $env_vars); // this is not super-duper awesome-secure, but definitely more secure than showing it on the CLI, or dropping temporary files with passwords in them.
        if ($proc_results === false) {
            return $this->commandFailure('Unable to invoke mysql via CLI');
        }

        // I'm not sure about these?
        stream_set_blocking($pipes[1], false); // use non-blocking reads for stdout
        stream_set_blocking($pipes[2], false); // use non-blocking reads for stderr

        // $this->info("Stdout says? ".fgets($pipes[1])); //FIXME: I think we might need to set non-blocking mode to use this properly?
        // $this->info("Stderr says? ".fgets($pipes[2])); //FIXME: ditto, same.
        // should we read stdout?
        // fwrite($pipes[0],config("database.connections.mysql.password")."\n"); //this doesn't work :(

        //$sql_contents = fopen($sqlfiles[0], "r"); //NOPE! This isn't a real file yet, silly-billy!

        try {
            if ( $this->option('sanitize-with-prefix') === null) {
                // "Legacy" direct-piping
                $bytes_read = 0;
                while (($buffer = fgets($sql_contents, SQLStreamer::$buffer_size)) !== false) {
                    $bytes_read += strlen($buffer);
                    // Log::debug("Buffer is: '$buffer'");
                    $write_offset = 0;
                    while ($write_offset < strlen($buffer)) {
                        $bytes_written = fwrite($pipes[0], substr($buffer, $write_offset));
                        if ($bytes_written === false || $bytes_written === 0) {
                            throw new RuntimeException('Unable to write to pipe');
                        }
                        $write_offset += $bytes_written;
                    }
                }
            } else {
                $sql_importer = new SQLStreamer($sql_contents, $pipes[0], $this->option('sanitize-with-prefix'));
                $bytes_read = $sql_importer->line_aware_piping();
            }
        } catch (\Exception $e) {
            Log::error("Error during restore!!!! ".$e->getMessage());
            // FIXME - put these back and/or put them in the right places?!
            $err_out = fgets($pipes[1]);
            $err_err = fgets($pipes[2]);
            Log::error("Error OUTPUT: ".$err_out);
            $this->info($err_out);
            Log::error("Error ERROR : ".$err_err);
            $this->error($err_err);
            throw $e;
        }
        if (!feof($sql_contents) || $bytes_read == 0) {
            return $this->commandFailure('Not at end of file for sql file, or zero bytes read. aborting!');
        }
    
        fclose($pipes[0]);
        fclose($sql_contents);
        
        $this->line(stream_get_contents($pipes[1]));
        fclose($pipes[1]);

        $this->error(stream_get_contents($pipes[2]));
        fclose($pipes[2]);

        //wait, have to do fclose() on all pipes first?
        $close_results = proc_close($proc_results);
        if ($close_results != 0) {
            return $this->commandFailure('There may have been a problem with the database import: Error number '.$close_results);
        }

        //and now copy the files over too (right?)
        //FIXME - we don't prune the filesystem space yet!!!!
        if ($this->option('no-progress')) {
            $bar = null;
        } else {
            $bar = $this->output->createProgressBar(count($interesting_files));
        }
        $sanitizer = new Sanitizer();

        foreach ($interesting_files as $file_details) {
            $file_stat = $za->statIndex($file_details['index']);
            if ($file_stat === false || ! isset($file_stat['name'])) {
                return $this->commandFailure('Unable to read file metadata from the backup.');
            }

            $ugly_file_name = $file_stat['name'];
            $destination_directory = dirname($file_details['target']);
            if (! is_dir($destination_directory)
                && ! mkdir($destination_directory, 0755, true)
                && ! is_dir($destination_directory)
            ) {
                return $this->commandFailure('Unable to create restore directory: '.$destination_directory);
            }

            if (strtolower(pathinfo($file_details['target'], PATHINFO_EXTENSION)) === 'svg') {
                $svg_contents = $za->getFromIndex($file_details['index']);
                if ($svg_contents === false) {
                    return $this->commandFailure('Unable to read SVG from the backup: '.$ugly_file_name);
                }

                $cleaned_svg = $sanitizer->sanitize($svg_contents);
                if ($cleaned_svg === false) {
                    return $this->commandFailure('Unsafe or invalid SVG was rejected: '.$ugly_file_name);
                }

                $bytes_written = file_put_contents($file_details['target'], $cleaned_svg, LOCK_EX);
                if ($bytes_written === false || $bytes_written !== strlen($cleaned_svg)) {
                    return $this->commandFailure('Unable to write restored SVG: '.$file_details['target']);
                }
            } else {
                $source_stream = $za->getStream($ugly_file_name);
                if ($source_stream === false) {
                    return $this->commandFailure('Unable to read file from the backup: '.$ugly_file_name);
                }

                $destination_stream = fopen($file_details['target'], 'wb');
                if ($destination_stream === false) {
                    fclose($source_stream);

                    return $this->commandFailure('Unable to open restore target: '.$file_details['target']);
                }

                $bytes_written = stream_copy_to_stream($source_stream, $destination_stream);
                $flushed = fflush($destination_stream);
                fclose($destination_stream);
                fclose($source_stream);

                if ($bytes_written === false || ! $flushed) {
                    return $this->commandFailure('Unable to completely restore file: '.$file_details['target']);
                }
            }

            if ($bar) {
                $bar->advance();
            }
        }
        if ($bar) {
            $bar->finish();
            $this->line('');
        } else {
            $this->info(count($interesting_files).' files were successfully transferred');
        }
        if (count($unsafe_files) > 0) {
            foreach ($unsafe_files as $unsafe_file) {
                $this->warn('Potentially unsafe file '.$unsafe_file.' was skipped');
            }
        }
        foreach ($boring_files as $boring_file) {
            $this->warn($boring_file.' was skipped.');
        }

        $za->close();

        return self::SUCCESS;
    }

    private function commandFailure(string $message): int
    {
        $this->error($message);

        return self::FAILURE;
    }
}
