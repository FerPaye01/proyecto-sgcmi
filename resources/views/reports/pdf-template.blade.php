<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 10px;
        }
        
        .header h1 {
            color: #1e3a8a;
            font-size: 18px;
            margin: 0 0 5px 0;
        }
        
        .header .subtitle {
            color: #64748b;
            font-size: 10px;
        }
        
        .meta-info {
            text-align: right;
            margin-bottom: 15px;
            color: #64748b;
            font-size: 9px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        table thead {
            background-color: #1e3a8a;
            color: white;
        }
        
        table th {
            padding: 8px 5px;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
            border: 1px solid #cbd5e1;
        }
        
        table td {
            padding: 6px 5px;
            border: 1px solid #e2e8f0;
            font-size: 9px;
        }
        
        table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        
        table tbody tr:hover {
            background-color: #e0f2fe;
        }
        
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #64748b;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <div class="subtitle">Sistema de Gestión de Coordinación Marítima Integrada</div>
    </div>
    
    <div class="meta-info">
        Generado: {{ $generated_at }}
    </div>
    
    @if(count($data) > 0)
        <table>
            <thead>
                <tr>
                    @foreach($headers as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($data as $row)
                    <tr>
                        @foreach($row as $cell)
                            <td>{{ $cell ?? '-' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="no-data">
            No hay datos disponibles para mostrar
        </div>
    @endif
    
    <div class="footer">
        SGCMI - Puerto de Matarani | Página generada automáticamente
    </div>
</body>
</html>
