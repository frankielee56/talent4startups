<?php

use Illuminate\Support\Facades\Redirect;
use Informulate\Core\CommandBus;
use Informulate\Forms\SignInForm;
use Informulate\Registration\Commands\RegisterUserCommand;
use Informulate\Users\Commands\UpdateProfileCommand;
use Informulate\Users\User;

class SessionsController extends BaseController
{

	use CommandBus;

	/**
	 * @var SignInForm
	 */
	private $signInForm;

	/**
	 * Constructor
	 *
	 * @param SignInForm $signInForm
	 */
	function __construct(SignInForm $signInForm)
	{
		$this->signInForm = $signInForm;

		$this->beforeFilter('guest', ['except' => 'destroy']);
	}


	/**
	 * Show the form for creating a new user.
	 *
	 * @return Response
	 */
	public function create()
	{
		return View::make('sessions.create');
	}


	/**
	 * Login the user
	 */
	public function store()
	{
		$formData = Input::only('email', 'password');
		$this->signInForm->validate($formData);

		if (Auth::attempt($formData)) {
			Flash::message('Welcome back to Talent4Startups!');

			// If the user is missing it's profile, force them to update their details
			$user = Auth::user();
			if (is_null($user->profile) or is_null($user->profile->first_name)) {
				return Redirect::to('profile');
			}

			return Redirect::intended('/');
		}

		return Redirect::to('login')->with('email', $formData['email'])->with('error', 'Wrong email/password entered.');
	}

	/**
	 * Login with linked in
	 *
	 * @return Response
	 */
	public function loginWithLinkedIn()
	{
		// get data from input
		$code = Input::get('code');
		$linkedInService = OAuth::consumer('Linkedin');
		$type = Session::pull('type') ?: Input::get('type');

		if (!empty($code)) {
			$token = $linkedInService->requestAccessToken($code);
			$result = json_decode($linkedInService->request('/people/~?format=json'), true);
			$email = json_decode($linkedInService->request('/people/~/email-address?format=json'), true);
			$user = User::where('email', '=', $email)->first();

			if (is_null($user) and $token) {
				if (is_null($type)) {
					Session::put('email', $email);
					Session::put('code', $code);
					Session::put('first_name', $result['firstName']);
					Session::put('last_name', $result['lastName']);
					Session::put('linked_in', $result['siteStandardProfileRequest']['url']);

					return View::make('sessions.select_type');
				}

				// We should have the type stored on the session if for whatever reason that fails, default to talents then.
				$user = $this->execute(
					new RegisterUserCommand($email, $email, $code, $type = Session::get('type') ?: 'talent')
				);

				$this->execute(
					new UpdateProfileCommand($user, [
						'first_name' => $result['firstName'],
						'last_name' => $result['lastName'],
						'linked_in' => $result['siteStandardProfileRequest']['url'],
						'published' => false
					])
				);

				Flash::message('Welcome to Talent4Startups');
			}

			if ($token) {
				Auth::login($user);

				if (is_null($user->profile) or count($user->profile->tags) === 0) {
					return Redirect::route('edit_profile');
				}

				return Redirect::intended('/');
			}
		}

		if (Route::currentRouteName() === 'register_linked_in' and is_null($type)) {
			return View::make('registration.select_type');
		}

		Session::put('type', $type);

		$url = $linkedInService->getAuthorizationUri(['state' => 'DCEEFWF45453sdffef424']); // TODO: What is this?

		return Redirect::to((string)$url);
	}

	/**
	 * Log the user out and redirect to the home page
	 *
	 * @return Redirect
	 */
	public function destroy()
	{
		Auth::logout();
		Flash::message('You have now been logged out');
		return Redirect::home();
	}

	/**
	 * Store the selected user type on the session
	 *
	 * Since we need to know the user type, and users might register with a social network, store the selected user type on the session
	 * This is most likely called via an ajax get request
	 *
	 * @return null
	 */
	public function storeUserType()
	{
		Session::put('type', Input::get('type'));
	}
}
