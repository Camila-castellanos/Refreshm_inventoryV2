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
        <th>Currency</th>
    </tr>
    @foreach ($items as $item)
    <tr>
        <td>#{{$item['id']}}</td>
        <td>{{$item['date'] ?? 'N/A'}}</td>
        <td>{{$item['manufacturer'] ?? 'N/A'}}</td>
        <td>{{$item['model'] ?? 'N/A'}}</td>
        <td>{{$item['colour'] ?? 'N/A'}}</td>
        <td>{{$item['issues'] ?? 'N/A'}}</td>
        <td>{{$item['imei'] ?? 'N/A'}}</td>
        <td>{{$item['grade'] ?? 'N/A'}}</td>
        <td>{{$item['selling_price'] ?? 'N/A'}}</td>
        <td>{{$item['currency'] ?? 'CAD'}}</td>
    </tr>
    @endforeach 
</table>