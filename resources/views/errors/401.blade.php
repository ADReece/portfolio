@extends('errors.layout')
@section('code', '401')
@section('title', __("Please log in"))
@section('message', __("You need to log in to see this page."))
@section('actions')
    <a class="primary" href="{{ route('login') }}">Log in</a>
    <a href="{{ url('/') }}">Home page</a>
@endsection
