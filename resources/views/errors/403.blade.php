@extends('errors.layout')
@section('code', '403')
@section('title', __("You don't have access to this page"))
@section('message', __("This page belongs to another account, or the link you used isn't valid. If someone sent you a link, ask them to send it again."))
