<center><h1>ITEMS REQUEST:</h1></center>
<b>Name:</b> {{$name}}<br><br>
<b>E-mail:</b> {{$email}}<br><br>
<b>Store:</b> {{$store}}<br><br>
<b>Notes:</b>
<p>
    {{$notes}}
</p>
</br>
<b>Items Requested</b>
<table>
    <tr>
        <th>ID</th>
        <th>Date</th>
        <th>Manufacturer</th>
        <th>Model</th>
        <th>Colour</th>
        <th>Issues</th>
        <th>IMEI</th>
        <th>Grade</th>
        <th>Price</th>
    </tr>
    @foreach ($items as $item)
    <tr>
        <td>#{{$item['id']}}</td>
        <td>{{$item['date']}}</td>
        <td>{{$item['manufacturer']}}</td>
        <td>{{$item['model']}}</td>
        <td>{{$item['colour']}}</td>
        <td>{{$item['issues']}}</td>
        <td>{{$item['imei']}}</td>
        <td>{{$item['grade']}}</td>
        <td>{{$item['selling_price']}}</td>
    </tr>
    @endforeach 
</table>