<?php

use Illuminate\Support\Facades\Redirect;
use Informulate\Forms\StartupForm;
use Informulate\Startups\Commands\CreateNewStartupCommand;
use Informulate\Core\CommandBus;
use Informulate\Startups\Commands\UpdateStartupCommand;
use Informulate\Startups\Startup;
use Informulate\Startups\StartupRepository;
use Informulate\Skills\Skill;
use Informulate\Users\User;
use Informulate\Tags\Tag;
use Informulate\Stages\Stage;

class StartupController extends BaseController
{

	use CommandBus;

	/**
	 * @var StartupForm
	 */
	private $startupForm;
	/**
	 * @var StartupRepository
	 */
	private $repository;

	/**
	 * Constructor
	 *
	 * @param StartupForm $startupForm
	 * @param StartupRepository $repository
	 */
	function __construct(StartupForm $startupForm, StartupRepository $repository)
	{
		$this->startupForm = $startupForm;
		$this->repository = $repository;

		$this->beforeFilter('auth', ['except' => ['index', 'show', 'search']]);
	}

	/**
	 * Index that shows all active startups.
	 *
	 * @return Response
	 */
	public function index()
	{
		$startups = $this->repository->allActive(Input::get('tag'), Input::get('needs'));

		if (Request::ajax()) {
			return View::make('startups.list')->with('startups', $startups)->render();
		}

		$needs = Skill::lists('name', 'id');

		return View::make('startups.index')->with('startups', $startups)->with('needs', $needs);
	}

	/**
	 * Show the form for creating a new user.
	 *
	 * @return Response
	 */
	public function create()
	{
		$tags = Tag::lists('name', 'id');
		$stages = Stage::lists('name', 'id');
		$needs = Skill::lists('name', 'id');

		return View::make('startups.create')->with('tags', $tags)->with('startupTags', '')->with('stages', $stages)->with('needs', $needs);
	}

	/**
	 * Save the user.
	 */
	public function store()
	{
		$this->startupForm->validate(Input::all());

		$startup = $this->execute(
			new CreateNewStartupCommand(Auth::user(), (object) Input::all())
		);

		Flash::message('New Startup Created');

		return Redirect::route('startups.show', ['url' => $startup->url]);
	}

	/**
	 * Display a startup
	 *
	 * @param $startup
	 * @return \Illuminate\View\View
	 */
	public function show($startup)
	{
		$startup = Startup::where('url', '=', $startup)->firstOrFail();
		$requests = $startup->members()->where('pending', true)->get();
		$members = $startup->members()->where('approved', true)->get();

		return View::make('startups.show')->with('startup', $startup)->with('requests', $requests)->with('members', $members);
	}

	public function approveMember($startup, $userId)
	{
		if ($startup->owner == Auth::user()) {
			$user = User::find($userId);
			if (false == $startup->hasMember($user)) {
				//
			}
		}
	}

	/*
	 * load view for edit startup with tags
	 * @param string $startup (url)
	 */
	public function edit($startup)
	{
		$startup = Startup::where('url', '=', $startup)->firstOrFail();
		$tags = Tag::lists('name', 'id');
		$stages = Stage::lists('name', 'id');
		$needs = Skill::lists('name', 'id');

		return View::make('startups.edit')->with('startup', $startup)->with('tags', $tags)->with('stages', $stages)->with('needs', $needs);
	}


	/*
	 * Update startup in storage
	 *
	 * @param $startup
	 */
	public function update($startup)
	{
		$this->startupForm->validate(Input::all());

		$startup = Startup::where('url', '=', $startup)->firstOrFail();

		$this->execute(
			new UpdateStartupCommand($startup, Input::all())
		);

		Flash::message('Startup updated successfully!');

		return Redirect::action('StartupController@show', $startup->url);
	}


	/**
	 * Destroy a record.
	 *
	 * @return Response
	 */
	public function destroy($id)
	{
		// TODO: Implement proper startup deactivation (We don't want to delete it we just want to deactivate it)
	}
}