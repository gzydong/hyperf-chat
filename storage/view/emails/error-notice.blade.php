<head>
    <base target="_blank"/>
    <style type="text/css">
        body {
            font-size: 14px;
            font-family: arial, verdana, sans-serif;
            line-height: 1.666;
            padding: 0;
            margin: 0;
            overflow: auto;
            white-space: normal;
            word-wrap: break-word;
            min-height: 100px
        }

        td, input, button, select, body {
            font-family: Helvetica, 'Microsoft Yahei', verdana
        }

        pre {
            white-space: pre-wrap;
            white-space: -moz-pre-wrap;
            white-space: -pre-wrap;
            white-space: -o-pre-wrap;
            word-wrap: break-word;
            width: 95%
        }

        th, td {
            font-family: arial, verdana, sans-serif;
            line-height: 1.666
        }

        img {
            border: 0
        }

        header, footer, section, aside, article, nav, hgroup, figure, figcaption {
            display: block
        }

        blockquote {
            margin-right: 0px
        }

        ::-webkit-scrollbar {
            display: none;
        }
    </style>
</head>
<body tabindex="0" role="listitem">
<table width="700" border="0" align="center" cellspacing="0" style="width:700px;">
    <tbody>
    <tr>
        <td>
            <div style="width:700px;margin:0 auto;border-bottom:1px solid #ccc;margin-bottom:30px;">
                <table border="0" cellpadding="0" cellspacing="0" width="700" height="39"
                       style="font:12px Tahoma, Arial, 宋体;">
                    <tbody>
                    <tr>
                        <td width="210"></td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div style="width:680px;padding:0 10px;margin:0 auto;">
                <div style="line-height:1.5;font-size:14px;margin-bottom:25px;color:#4d4d4d;">
                    <strong style="display:block;margin-bottom:15px;font-size: 16px;">
                        系统报错通知：{{date('Y-m-d H:i:s')}}
                    </strong>
                </div>
                <div style="margin-bottom:30px;">
                    <p>{{$message}}</p>
                    <pre>{{$throwable}}</pre>
                </div>
            </div>
        </td>
    </tr>
    </tbody>
</table>
</body>
