<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Robosto Grocery App</title>
    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
</head>
<body>

    <div class="min-h-screen w-full bg-gray-300">
        <div class="max-w-screen-md mx-auto px-10 pt-20">
            <div class="bg-white md:h-48 rounded-lg shadow-md flex flex-wrap flex-col-reverse md:flex-col">
            <div class="w-full md:w-1/2 p-4">
                <img src="https://www.robostodelivery.com/r2.png" alt="" class="w-20">
                <br/>
                <h3 class="text-4xl text-red-600 mb-4 font-bold">Failed</h3>
                <p class="text-sm">
                    Sorry, We could not add your credit card.
                </p>
            </div>
            <div class="w-full md:w-1/2 p-4 md:p-0 hidden md:block">
                <img src="https://www.robostodelivery.com/r.png" alt="" class="w-64 mx-auto">
            </div>
            </div>
        </div>
    </div>
    
</body>
</html>