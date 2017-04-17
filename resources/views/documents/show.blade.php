@extends('layouts.app')

@section('pageTitle', $document->title)

@push('meta')
    <meta property="og:description" content="{{ $document->shortIntroText() }}">
    <meta property="og:image" content="{{ url($document->getFeaturedImageUrl()) }}">
    <meta name="twitter:card" content="summary_large_image">
@endpush

@section('content')
    @include('components.errors')

    @can('viewManage', $document)
        <div class="btn-group pull-right">
            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-cog"></i></button>
            <ul class="dropdown-menu">
                <li><a href="{{ route('documents.manage.settings', $document) }}">@lang('messages.edit')</a></li>
                <li><a href="{{ route('documents.manage.comments', $document) }}">@lang('messages.document.moderate')</a></li>
            </ul>
        </div>
    @endcan

    <div id="doc-header">
        <h1>{{ $document->title }}</h1>
        <p class="sponsors">
            @lang('messages.document.sponsoredby', ['sponsors' => $document->sponsors->implode('display_name', ', ')])
        </p>

        @if (!empty($document->introtext))
            <div class="introtext">
                {!! $document->introtext_html !!}
            </div>
        @endif
    </div>

    <div id="doc-content">
        <div class="row">
            <div id="document-outline" class="col-md-3 panel hidden-sm hidden-xs small">
                <ul class="nav"></ul>
            </div>

            <div class="col-md-8 col-sm-11">
                @include('documents.partials.support-btns')

                <section id="page_content">
                    {!! $documentPages->first()->rendered() !!}
                </section>

                {{ $documentPages->appends(request()->query())->fragment('page_content')->links() }}
            </div>

            <aside class="annotation-container col-md-1"></aside>
        </div>
    </div>

    @if ($document->discussion_state !== \App\Models\Doc::DISCUSSION_STATE_HIDDEN)
        <div id="comments">
            <div class="row comments">
                <section class="col-md-offset-2 col-md-8">
                    @if ($document->discussion_state === \App\Models\Doc::DISCUSSION_STATE_OPEN)
                        <div class="floating-card">
                            @if (Auth::user())
                                @include('documents.partials.new-comment-form', ['route' => ['documents.comments.store', $document], 'message' => 'messages.document.add_comment'])
                            @else
                                {{ Html::linkRoute('login', trans('messages.document.login_to_comment'), ['redirect' => $document->url]) }}
                            @endif
                        </div>
                    @endif

                    <ul class="media-list">
                        @each('documents.partials.comment-li', $comments, 'comment')
                    </ul>
                    <div class="text-center">
                        @include('components.pagination', ['collection' => $comments])
                    </div>
                </section>
            </div>
        </div>
    @endif

    @push('scripts')
        <script src="{{ elixir('js/annotator-madison.js') }}"></script>
        <script src="{{ elixir('js/document.js') }}"></script>
        <script>
            loadTranslations([
                'messages.close',
                'messages.document.add_reply',
                'messages.document.collaborators_count',
                'messages.document.flag',
                'messages.document.like',
                'messages.document.note',
                'messages.document.note_edit_explanation_prompt',
                'messages.document.note_reply',
                'messages.document.notes',
                'messages.document.replies_count',
                'messages.edit',
                'messages.none',
                'messages.permalink',
                'messages.submit'
            ])
            .done(function () {
                window.buildDocumentOutline('#document-outline', '#page_content');

                @if ($document->discussion_state !== \App\Models\Doc::DISCUSSION_STATE_HIDDEN)
                    loadAnnotations(
                        "#page_content",
                        ".annotation-container",
                        {{ $document->id }},
                        {{ request()->user() ? request()->user()->id : 'null' }},
                        {{ $document->discussion_state !== \App\Models\Doc::DISCUSSION_STATE_OPEN ? 1 : 0 }}
                    );

                    // race-y with loading annotaions, so it's called again
                    // in annotator-madison.js after annotator.js has loaded
                    // it's stuff
                    revealComment({{ $document->id }});
                    window.onhashchange = revealComment.bind(this, {{$document->id }});

                    if (window.getQueryParam('comment_page')) {
                        showComments();
                    }

                    $('.comment-replies-toggle-show').click(function(e) {
                        e.preventDefault();
                        let commentId = $(e.target).data('comment-id');
                        toggleCommentReplies(commentId);
                    });

                    $('.comment a.action-link').click(function(e) {
                        e.preventDefault();
                        $(e.target).trigger('madison.addAction');
                    });
                @endif
            });
        </script>
    @endpush
@endsection