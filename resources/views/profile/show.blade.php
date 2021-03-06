@extends('app')

@section('content')
	<div class="row">
		<div class="col-md-3">
			<img class="img-circle img-responsive img-rounded" src="{{ $user->avatar() }}?s=150&d=mm" alt=""  height="150" width="150"/>
			<input data-id="{{ $user->id }}" type="number" class="member-rating-view" value="{{ $user->rating() }}" }}>
		</div>
		<div class="col-md-9">
			<h1>Hi, I’m {{ $user->profile->first_name }} {{ $user->profile->last_name }} located in {{ $user->profile->location }}.</h1>
			@if($user->profile->profession)
				<h4>I work in {!! $user->profile->profession->name !!}</h4>
			@endif
			@include('layouts.partials.socialshare')
		</div>
	</div>
	<div class="pull-left">
		@if (Auth::user() and Auth::id() == $user->id)
			<p><a href="{{ route('edit_profile') }}" class="btn btn-primary">Edit Profile</a></p>
		@else
			<p><a href="{{ route('messages.create', $user->id) }}" class="btn btn-primary">Contact</a></p>
			<div>
				@if (Auth::user() and Auth::user()->startups()->lists('name','id')->count() > 0)
					{!! Form::open(['route' => 'invite_to_startup']) !!}
					{!! Form::hidden('user_id', $user->id) !!}
					{!! Form::submit('Invite To', ['class' => 'btn btn-primary']) !!}
					{!! Form::select('startup_id', Auth::user()->startups()->lists('name','id'), null, ['class' => 'btn btn-default']) !!}
					{!! Form::close() !!}
				@endif
			</div>
            @if (Auth::user())
			@foreach(Auth::user()->startups as $startup)
				@foreach($startup->members()->where('status', 'pending')->where('user_id', $user->id)->with('profile')->get() as $user)
				<div>
					<a href="{{ route('profile_path', $user->id) }}">Applied to join {{ $startup->name }}
					</a> <a class="btn btn-primary btn-xs" href="{{ route('startup_membership_update', ['startup' => $startup->url, 'userId' => $user->id, 'action' => 'approve']) }}">Approve</a>
					<a class="btn btn-primary btn-xs" href="{{ route('startup_membership_update', ['startup' => $startup->url, 'userId' => $user->id, 'action' => 'reject']) }}">Reject</a>
				</div>
				@endforeach
			@endforeach
            @endif
		@endif
	</div>
	<div class="row">
		<div class="col-sm-12">
			<h2>My Interests</h2>
			@if(count($user->profile->tags) > 0)
				@foreach($user->profile->tags as $tag)
					<a href="{{{ route('talents.index') }}}?tag={{ $tag->name }}"><span class="badge">{{ $tag->name }}</span></a>
				@endforeach
			@endif

			<p> {{ $user->profile->about }}</p>
		</div>
	</div>
	<div class="row">
		<div class="col-sm-12">
			<h2>Startups I’m involved in</h2>
			@if(count($user->contributions) > 0 || count($user->startups) > 0)
				@foreach($user->contributions as $startup)
					@if($startup->pivot->status == 'approved')
				<div class="col-sm-3">
					<div class="clearfix">
						<h4><a href="{{ route('startups.show', $startup->url) }}">{{ $startup->name }}</a> <small>By: {{ $startup->owner->profile->first_name }} {{ $startup->owner->profile->last_name }}</small></h4>
						<p>{{ str_limit( $startup->description, 50 ) }}</p>
					</div>
					<div class="clearfix">
						@if (Auth::user() and Auth::user()->username == $user->username)
							<p><a href="{{ route('startups.edit', ['startup' => $startup->url]) }}" class="btn btn-primary btn-xs pull-right" role="button">Edit</a></p>
						@endif
					</div>
				</div>
					@endif
				@endforeach
					@foreach($user->startups as $startup)
						<div class="col-sm-3">
							<div class="clearfix">
								<h4><a href="{{ route('startups.show', $startup->url) }}">{{ $startup->name }}</a> <small>By: {{ $startup->owner->profile->first_name }} {{ $startup->owner->profile->last_name }}</small></h4>
								<p>{{ str_limit( $startup->description, 50 ) }}</p>
							</div>
							<div class="clearfix">
								@if (Auth::user() and Auth::user()->username == $user->username)
									<p><a href="{{ route('startups.edit', ['startup' => $startup->url]) }}" class="btn btn-primary btn-xs pull-right" role="button">Edit</a></p>
								@endif
							</div>
						</div>
					@endforeach
			@else
				<div class="alert alert-info">
					I'm not currently involved in any startup.
				</div>
			@endif
		</div>
	</div>
@stop

@section('javascript')
	<script type="text/javascript">
		$(document).ready(function () {
			$('.member-rating-view').rating({
				readonly: true,
				showClear: false,
				showCaption: false,
				hoverEnabled: false,
				size: 'xs'
			});
		});
	</script>
@stop
