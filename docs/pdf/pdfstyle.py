from reportlab.lib.pagesizes import letter
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.units import inch
from reportlab.lib import colors
from reportlab.platypus import (SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle,
                                PageBreak, KeepTogether)

NAVY   = colors.HexColor('#222b59')
BLUE   = colors.HexColor('#0A3D91')
GREEN  = colors.HexColor('#166534')
AMBER  = colors.HexColor('#92400e')
RED    = colors.HexColor('#b91c1c')
GREY   = colors.HexColor('#6b7280')
LIGHT  = colors.HexColor('#f8f9fb')
BORDER = colors.HexColor('#d1d5db')

def styles():
    s = getSampleStyleSheet()
    return {
        'title':   ParagraphStyle('t', parent=s['Title'], fontSize=24, leading=28,
                                  textColor=NAVY, spaceAfter=4, alignment=0),
        'sub':     ParagraphStyle('sub', parent=s['Normal'], fontSize=11, leading=15,
                                  textColor=GREY, spaceAfter=18),
        'h1':      ParagraphStyle('h1', parent=s['Heading1'], fontSize=15, leading=19,
                                  textColor=NAVY, spaceBefore=18, spaceAfter=8),
        'h2':      ParagraphStyle('h2', parent=s['Heading2'], fontSize=11.5, leading=15,
                                  textColor=BLUE, spaceBefore=12, spaceAfter=5),
        'body':    ParagraphStyle('b', parent=s['Normal'], fontSize=9.6, leading=14,
                                  spaceAfter=7),
        'bullet':  ParagraphStyle('bu', parent=s['Normal'], fontSize=9.6, leading=13.5,
                                  leftIndent=14, bulletIndent=4, spaceAfter=3),
        'note':    ParagraphStyle('n', parent=s['Normal'], fontSize=9, leading=13,
                                  textColor=GREY, spaceAfter=7),
        'cell':    ParagraphStyle('c', parent=s['Normal'], fontSize=8.6, leading=11.5),
        'cellb':   ParagraphStyle('cb', parent=s['Normal'], fontSize=8.6, leading=11.5,
                                  fontName='Helvetica-Bold'),
        'callout': ParagraphStyle('co', parent=s['Normal'], fontSize=9.6, leading=14,
                                  leftIndent=10, rightIndent=10, spaceBefore=4, spaceAfter=4),
    }

def table(data, widths, st, header=True, zebra=True):
    t = Table(data, colWidths=widths, repeatRows=1 if header else 0)
    cmds = [
        ('VALIGN', (0,0), (-1,-1), 'TOP'),
        ('LINEBELOW', (0,0), (-1,-2), 0.4, BORDER),
        ('TOPPADDING', (0,0), (-1,-1), 5),
        ('BOTTOMPADDING', (0,0), (-1,-1), 5),
        ('LEFTPADDING', (0,0), (-1,-1), 7),
        ('RIGHTPADDING', (0,0), (-1,-1), 7),
    ]
    if header:
        cmds += [('BACKGROUND', (0,0), (-1,0), LIGHT),
                 ('LINEBELOW', (0,0), (-1,0), 0.9, NAVY)]
    if zebra:
        for i in range(1, len(data)):
            if i % 2 == 0:
                cmds.append(('BACKGROUND', (0,i), (-1,i), colors.HexColor('#fcfcfd')))
    t.setStyle(TableStyle(cmds))
    return t

def callout(text, st, colour=BLUE, bg='#f5f8ff'):
    p = Paragraph(text, st['callout'])
    t = Table([[p]], colWidths=[6.6*inch])
    t.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,-1), colors.HexColor(bg)),
        ('LINEBEFORE', (0,0), (0,-1), 2.5, colour),
        ('LEFTPADDING', (0,0), (-1,-1), 10),
        ('RIGHTPADDING', (0,0), (-1,-1), 10),
        ('TOPPADDING', (0,0), (-1,-1), 8),
        ('BOTTOMPADDING', (0,0), (-1,-1), 8),
    ]))
    return t

def build(path, title, subtitle, story_fn):
    st = styles()
    doc = SimpleDocTemplate(path, pagesize=letter,
                            leftMargin=0.85*inch, rightMargin=0.85*inch,
                            topMargin=0.8*inch, bottomMargin=0.8*inch,
                            title=title, author='USSCOS')
    story = [Paragraph(title, st['title']), Paragraph(subtitle, st['sub'])]
    story_fn(story, st)

    def footer(canvas, d):
        canvas.saveState()
        canvas.setFont('Helvetica', 7.5)
        canvas.setFillColor(GREY)
        canvas.drawString(0.85*inch, 0.5*inch, title)
        canvas.drawRightString(7.65*inch, 0.5*inch, f"Page {canvas.getPageNumber()}")
        canvas.setStrokeColor(BORDER)
        canvas.line(0.85*inch, 0.62*inch, 7.65*inch, 0.62*inch)
        canvas.restoreState()

    doc.build(story, onFirstPage=footer, onLaterPages=footer)
    return path
