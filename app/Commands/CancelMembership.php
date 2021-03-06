<?php namespace App\Commands;

use App\Models\Startup;
use App\Models\User;
use App\Repositories\StartupRepository;
use Illuminate\Contracts\Bus\SelfHandling;

class CancelMembership extends Command implements SelfHandling {

	protected $user, $startup;

	/**
	 * Create a new command instance.
	 *
	 * @param User $user
	 * @param $startup
	 */
	public function __construct(User $user, Startup $startup)
	{
		$this->user = $user;
		$this->startup = $startup;
	}

	/**
	 * Execute the command.
	 *
	 * @param StartupRepository $repository
	 * @return static
	 */
	public function handle(StartupRepository $repository)
	{
		if ($this->startup->hasMember($this->user)) {
			$repository->cancelMembershipRequest($this->user, $this->startup);
		}

		return $this->startup;
	}

}
