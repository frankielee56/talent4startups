<?php

use Illuminate\Support\Facades\Redirect;
use Informulate\Forms\ProfileForm;
use Informulate\Forms\ResetForm;
use Informulate\Core\CommandBus;
use Informulate\Users\Commands\UpdateProfileCommand;
use Informulate\Users\User;
use Informulate\Startups\Startup;
use Informulate\Tags\Tag;
use Informulate\Users\UserRepository;
use Informulate\Skills\Skill;

class ProfileController extends BaseController
{

	use CommandBus;

	/**
	 * @var ProfileForm
	 */
	private $profileForm;

	/**
	 * @var ResetForm
	 */
	private $resetForm;
	/**
	 * @var UserRepository
	 */
	private $userRepository;

	/**
	 * Constructor
	 *
	 * @param ProfileForm $profileForm
	 * @param ResetForm $resetForm
	 * @param UserRepository $userRepository
	 */
	function __construct(ProfileForm $profileForm, ResetForm $resetForm, UserRepository $userRepository)
	{
		$this->profileForm = $profileForm;
		$this->resetForm = $resetForm;
		$this->userRepository = $userRepository;
		$this->beforeFilter('auth', ['except' => ['show']]);
	}

	/**
	 * Show the users public profile
	 *
	 * @param $username
	 * @return $this
	 */
	public function show($username)
	{
		$user = $this->userRepository->findByUsername($username);

		return View::make('profile.show')->with('user', $user)->with('startups', $user->startups)->with('contributions', $user->contributions);
	}

	/**
	 * Show the form for creating a user profile.
	 *
	 * @return Response
	 */
	public function edit()
	{
		$user = Auth::user();
		$describes = Skill::lists('name', 'id');
		$skills = Tag::lists('name', 'id');
		return View::make('profile.edit')->with('user', $user)->with('describes', $describes)->with('skills', $skills);
	}

	/**
	 * Save the user.
	 */
	public function store()
	{
		$this->profileForm->validate(Input::all());

		$this->execute(
			new UpdateProfileCommand(Auth::user(), Input::all())
		);

		Flash::message('Your profile has been updated successfully!');

		//redirect to home, if user is talent
		if (Input::get('type') == 'startup') {

			// redirect to create projct if no project added by startup yet.
			$projects = Startup::where('user_id', '=', Auth::user()->id)->count();

			if ($projects == 0) {
				return Redirect::route('startups.create');
			}
		}

		return Redirect::intended('');
	}

	/**
	 * Load view for reset password for logged in users
	 */
	public function resetPasswordForm()
	{

		return View::make('profile.reset_password');
	}

	/**
	 * Reset requested password for user
	 */
	public function resetPassword()
	{
		$this->resetForm->validate(Input::all());

		extract(Input::only('old_password', 'new_password', 'password_confirmation'));

		if ($new_password != $password_confirmation) {
			//confrim password not match
			return redirect::route('reset_password')->with('error', 'Confirm password not match');
		}

		//check if user entered old password correct
		$user = User::find(Auth::id());
		if (Hash::check($old_password, $user->password)) {
			//old password correct
			try {
				//save user with new password, display success message

				$user->password = $new_password;
				$user->save();
				Flash::message('Your password has been reset successfully!');
				return redirect::route('reset_password');
			} catch (Exception $e) {
				// fail to update user, generate error and load view

				Flash::message('Error in reset password. Try again later!');
				return redirect::route('reset_password')->with('error', 'Error in reset password. Try again later!');
			}
		} else {
			// generate error if old password is incorrect
			return redirect::route('reset_password')->with('error', 'Old password is incorrect!');
		}
	}
}
