<!DOCTYPE html>

<html lang="en">



<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="{{ csrf_token() }}">



    <title>Reorder Format</title>

    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

    <style>

        body {

            font-family: Arial, sans-serif;

            background-color: #f2f2f2;

            padding: 20px;

        }



        .container {

            max-width: 800px;

            margin: 0 auto;

            background-color: #fff;

            padding: 20px;

            border-radius: 5px;

            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);

        }



        h1 {

            text-align: center;

            margin-bottom: 20px;

        }



        .section {

            margin-bottom: 20px;

            padding: 10px;

            border: 1px solid #ccc;

            border-radius: 5px;

            background-color: #f9f9f9;

        }



        .section h2 {

            margin-top: 0;

        }



        .question {

            margin-bottom: 10px;

            padding: 5px;

            border: 1px solid #f0f0f0;

            background-color: #fff;

            border-radius: 3px;

        }



        input[type="number"] {

            width: 60px;

            padding: 5px;

            font-size: 14px;

            border: 1px solid #ccc;

            border-radius: 3px;

            text-align: center;

        }



        ul {

            list-style-type: none;

            padding: 0;

        }

    </style>

</head>



<body>

    <div class="container">

        <h1>Reorder Format</h1>

        <div id="sections">

            @foreach ($format->sections as $section)

            <div class="section" data-id="{{ $section->id }}">

                <h2>{{ $section->section_name }}</h2>

                <input type="number" class="section-order" value="{{ $section->orderby }}" data-id="{{ $section->id }}" />

                <ul class="questions">

                    @foreach ($section->questions as $question)

                    <li class="question" data-id="{{ $question->id }}">

                        {{ $question->question_name }}

                        <input type="number" class="question-order" value="{{ $question->orderby }}" data-id="{{ $question->id }}" />

                    </li>

                    @endforeach

                </ul>

            </div>

            @endforeach

        </div>

    </div>



    <script>

        $(document).ready(function() {

            // Section order change handler

            $('.section-order').change(function() {

                var sectionId = $(this).data('id');

                var newOrder = $(this).val();

                updateOrder('https://online.startexmarketing.com/superadmin/updateSectionOrder', sectionId, newOrder, 'Section');

            });



            // Question order change handler

            $('.question-order').change(function() {

                var questionId = $(this).data('id');

                var newOrder = $(this).val();

                updateOrder('https://online.startexmarketing.com/superadmin/updateQuestionOrder', questionId, newOrder, 'Question');

            });



            function updateOrder(url, id, order, itemName) {

                $.ajax({

                    url: url,

                    method: 'POST',

                    data: {

                        _token: $('meta[name="csrf-token"]').attr('content'),

                        item_id: id,

                        new_order: order

                    },

                    success: function(response) {

                        console.log(itemName + ' order updated successfully.');

                    },

                    error: function(err) {

                        console.error('Error updating ' + itemName.toLowerCase() + ' order.');

                    }

                });

            }

        });

    </script>

</body>



</html>