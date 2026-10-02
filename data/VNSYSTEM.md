Sub DownloadReportWithLogin()
    Dim selectedMonth As String
    Dim validDate As Boolean
    Dim dept As String
    Dim bearerToken As String
    
    ' Ask user for month
    Do
        selectedMonth = InputBox("Enter the report month (format: YYYY-MM)", "Select Month", Format(Date, "yyyy-mm"))
        If selectedMonth = "" Then ans1 = "Cancelled downloading report": Exit Sub ' User cancelled

        validDate = IsDate(selectedMonth & "-01")
        If Not validDate Then MsgBox "Invalid month format input, please use 'YYYY-MM'"
    Loop Until validDate

    ' Ask user for report type
    dept = ChooseReportType()
    If dept = "" Then Exit Sub

    ' Get token
    bearerToken = GetBearerToken()
    If bearerToken = "" Then
        MsgBox "Failed to retrieve token.", vbCritical
        Exit Sub
    End If

    ' Download report
    DownloadReport dept, selectedMonth, bearerToken
    
    'import report
    If dept = "machining" Then
        ThisWorkbook.Sheets("Data_SX_RAW").UsedRange.Clear
        Data_SX
    ElseIf dept = "mxmh" Then
        ThisWorkbook.Sheets("Data_MX_RAW").UsedRange.Clear
        Data_MX
    ElseIf dept = "bonded" Then
        ThisWorkbook.Sheets("NQ").UsedRange.Clear
        Data_NQ
    ElseIf dept = "cleaning" Then
        ThisWorkbook.Sheets("Cleaning").UsedRange.Clear
        Data_Cleaning
    ElseIf dept = "AuxSX" Then
        ThisWorkbook.Sheets("DATA_AUX_SX").UsedRange.Clear
        Data_AuxSX
        AuxiliarySX_Fixed
        AuxiliarySX_Fixed_2
    ElseIf dept = "AuxMX" Then
        ThisWorkbook.Sheets("DATA_AUX_MX").UsedRange.Clear
        Data_AuxMX
        AuxiliaryMX_Fixed
        AuxiliaryMX_Fixed_2
    End If
End Sub

Public Function GetBearerToken() As String
    Dim http As Object
    Dim url As String
    Dim boundary As String
    Dim postData As String
    Dim response As String
    Dim token As String
    Dim tokenStart As Long, tokenEnd As Long
    Dim pass As String
    Dim resultOK As Boolean
    Dim msgStart As Long, msgEnd As Long
    Dim messageText As String
    Dim maxAttempts As Integer: maxAttempts = 5
    Dim attempt As Integer

    ' Return token if already retrieved in this session (valid for 1 hour)
    If g_BearerToken <> "" And (Now - g_TokenTime) * 24 * 60 < 59 Then
        GetBearerToken = g_BearerToken
        Exit Function
    End If

    url = "https://vnsystem.smcmfg.com.vn:8490/Login/SignInVerify"
    boundary = "----WebKitFormBoundarylJ9qTDcFgyUrGhpS"
    Set http = CreateObject("MSXML2.XMLHTTP")

    For attempt = 1 To maxAttempts
        pass = InputBox(Environ("Username") & vbCrLf & vbCrLf & "Enter your password:", "Login Attempt " & attempt & " of " & maxAttempts)
        If pass = "" Then Exit Function ' User cancelled

        postData = "--" & boundary & vbCrLf & _
            "Content-Disposition: form-data; name=""UserName""" & vbCrLf & vbCrLf & _
            Environ("Username") & vbCrLf & _
            "--" & boundary & vbCrLf & _
            "Content-Disposition: form-data; name=""Password""" & vbCrLf & vbCrLf & _
            pass & vbCrLf & _
            "--" & boundary & "--"

        With http
            .Open "POST", url, False
            .setRequestHeader "Content-Type", "multipart/form-data; boundary=" & boundary
            .send postData
            response = .responseText
        End With

        ' Check if login was successful
        resultOK = InStr(response, """result"":true") > 0

        If resultOK Then
            msgStart = InStr(response, """message"":""")
            If msgStart > 0 Then
                msgStart = msgStart + Len("""message"":""")
                msgEnd = InStr(msgStart, response, """")
                messageText = Mid(response, msgStart, msgEnd - msgStart)

                ' Store and return token
                g_BearerToken = messageText
                g_TokenTime = Now
                GetBearerToken = messageText
                Exit Function
            End If
        Else
            ans1 = "Incorrect password. Please try again"
        End If
    Next attempt

    ans1 = "Maximum login attempts reached. Please try again"
    GetBearerToken = ""
End Function

Private Function ChooseReportType() As String
    Dim ans As Variant
    ans = Application.InputBox("Please choose:" & vbCrLf & _
                                "1: Machining" & vbCrLf & _
                                "2: MX/MH" & vbCrLf & _
                                "3: Bonded" & vbCrLf & _
                                "4: Cleaning" & vbCrLf & _
                                "5: Auxiliary Machining" & vbCrLf & _
                                "6: Auxiliary MX", Type:=2)
    Select Case LCase(ans)
        Case "1":
            ChooseReportType = "machining"
            ans1 = "Downloaded machining report"
        Case "2": ChooseReportType = "mxmh"
            ans1 = "Downloaded MX/MH report"
        Case "3": ChooseReportType = "bonded"
            ans1 = "Downloaded bonded report"
        Case "4": ChooseReportType = "cleaning"
            ans1 = "Downloaded cleaning report"
        Case "5": ChooseReportType = "AuxSX"
            ans1 = "Downloaded Auxiliary machining report"
        Case "6": ChooseReportType = "AuxMX"
            ans1 = "Downloaded Auxiliary MX report"
        Case Else: ChooseReportType = ""
            ans1 = "Cancelled downloading report"
    End Select
    
End Function

Private Sub DownloadReport(department As String, selectedDate As String, token As String)
    Dim http As Object
    Dim filePath As String
    Dim binaryStream As Object
    Dim fileName As String
    Dim urlPrefix As String
    Dim urlfinalfix As String
    downloadmsg = True

    Select Case LCase(department)
        Case "machining"
            urlPrefix = "https://vnsystem.smcmfg.com.vn:8492/Plastic/ExtrusionReport?typeReport=Production+Performance&timeType=Month&time="
            'urlPrefix = "https://vnsystem.smcmfg.com.vn:8492/Cylinder/MachiningReport?typeReport=Production+Performance&timeType=Month&time="
            'https://vnsystem.smcmfg.com.vn:8492/Plastic/ExtrusionReport?typeReport=Production+Performance&timeType=Month&time=2025-10&ProductionType=P
        Case "cleaning"
            urlPrefix = "https://vnsystem.smcmfg.com.vn:8492/Cylinder/CleanMachiningReport?typeReport=Production+Performance&timeType=Month&time="
        Case "bonded"
            urlPrefix = "https://vnsystem.smcmfg.com.vn:8492/Cylinder/BondedReport?typeReport=Production+Performance&timeType=Month&time="
        Case "mxmh"
            urlPrefix = "https://vnsystem.smcmfg.com.vn:8492/MXMHMachining/ExportAdvanceMXMHReport?typeReport=Production+Performance&timeType=Month&time="
        Case "auxsx"
            urlPrefix = "https://vnsystem.smcmfg.com.vn:8492/Cylinder/ExportAdvanceReportCombineMachining?timeType=Month&time="
        Case "auxmx"
            urlPrefix = "https://vnsystem.smcmfg.com.vn:8492/MXMHMachining/ExportAdvanceReportCombine?timeType=Month&time="
        Case Else
            ans1 = "Unknown option selected."
            Exit Sub
    End Select
    urlfinalfix = "=&ProductionType=P"

    fileName = department & "_Report_" & selectedDate & ".xlsx"
    filePath = Environ("USERPROFILE") & "\Downloads\" & fileName

    Set http = CreateObject("MSXML2.XMLHTTP")
    With http
        .Open "POST", urlPrefix, False
        ' .Open "POST", urlPrefix & selectedDate & urlfinalfix, False
        .setRequestHeader "Authorization", "Bearer " & token
        .setRequestHeader "Content-Type", "application/x-www-form-urlencoded"
        .send ""
        If .Status = 200 Then
            Set binaryStream = CreateObject("ADODB.Stream")
            With binaryStream
                .Type = 1
                .Open
                .Write http.responseBody
                .SaveToFile filePath, 2
                .Close
            End With
        Else
            ans1 = "Download failed - Internal sever error!"
            Exit Sub
        End If
    End With
End Sub