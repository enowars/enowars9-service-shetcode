import random

def generate_feedback_message():
    feedback_messages = [
        "Seamless temporal jump, no paradox introduced",
        "Smooth service with zero time distortions",
        "Chrono service exceeded my past expectations",
        "Reliable time travel, arrived before I left",
        "Best temporal commute I’ve ever experienced",
        "Staff ensured safe travels across centuries",
        "Fast, fun, and paradox-free experience",
        "Top-notch chronoshuttle, never felt safer",
        "Service restored my faith in time travel",
        "Five stars for chronological precision"
    ]
    return random.choice(feedback_messages)

def generate_simple_svg(text: str):
    svg_templates = [
         """<?xml version="1.0" encoding="UTF-8"?>
<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg">
    <circle cx="50" cy="50" r="40" stroke="black" stroke-width="3" fill="red" />
    <text x="50" y="55" text-anchor="middle" fill="white" font-family="Arial" font-size="12">{text}</text>
</svg>""",
        """<?xml version="1.0" encoding="UTF-8"?>
<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg">
    <rect x="10" y="10" width="80" height="80" fill="blue" stroke="black" stroke-width="2" />
    <text x="50" y="55" text-anchor="middle" fill="white" font-family="Arial" font-size="12">{text}</text>
</svg>""",
        """<?xml version="1.0" encoding="UTF-8"?>
<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg">
    <polygon points="50,10 90,90 10,90" fill="green" stroke="black" stroke-width="2" />
    <text x="50" y="70" text-anchor="middle" fill="white" font-family="Arial" font-size="12">{text}</text>
</svg>""",
        """<?xml version="1.0" encoding="UTF-8"?>
<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg">
    <ellipse cx="50" cy="50" rx="45" ry="30" fill="purple" stroke="black" stroke-width="2" />
    <text x="50" y="55" text-anchor="middle" fill="white" font-family="Arial" font-size="12">{text}</text>
</svg>""",
        """<?xml version="1.0" encoding="UTF-8"?>
<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg">
    <line x1="10" y1="10" x2="90" y2="90" stroke="orange" stroke-width="10" />
    <line x1="90" y1="10" x2="10" y2="90" stroke="orange" stroke-width="10" />
    <text x="50" y="55" text-anchor="middle" fill="black" font-family="Arial" font-size="12">{text}</text>
</svg>""",
        """<?xml version="1.0" encoding="UTF-8"?>
<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg">
    <path d="M20,50 Q50,20 80,50 Q50,80 20,50" fill="pink" stroke="black" stroke-width="2" />
    <text x="50" y="55" text-anchor="middle" fill="black" font-family="Arial" font-size="12">{text}</text>
</svg>""",
        """<?xml version="1.0" encoding="UTF-8"?>
<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg">
    <circle cx="50" cy="50" r="40" fill="yellow" stroke="brown" stroke-width="2" />
    <circle cx="35" cy="40" r="8" fill="brown" />
    <circle cx="65" cy="40" r="8" fill="brown" />
    <path d="M30,60 Q50,80 70,60" fill="none" stroke="brown" stroke-width="2" />
    <text x="50" y="55" text-anchor="middle" fill="black" font-family="Arial" font-size="12">{text}</text>
</svg>"""
    ]

    return random.choice(svg_templates).format(text=text)