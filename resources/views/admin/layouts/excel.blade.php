<table class="table tabler-border">
    <tbody>

    @foreach ($excelData as $row)
        <tr>
            @for( $i = 0; $i < 17; $i++)
                <td>{{$row[$i]}}</td>
            @endfor
        </tr>
    @endforeach

    </tbody>
</table>
